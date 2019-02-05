<?php
/**
 * Stores all data to communicate with the MyParcel API
 *
 * If you want to add improvements, please create a fork in our GitHub:
 * https://github.com/myparcelnl
 *
 * @author      Reindert Vetter <reindert@myparcel.nl>
 * @copyright   2010-2017 MyParcel
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US  CC BY-NC-ND 3.0 NL
 * @link        https://github.com/myparcelnl/sdk
 * @since       File available since Release v0.1.0
 */

namespace MyParcelNL\Sdk\src\Helper;

use MyParcelNL\Sdk\src\Adapter\ConsignmentAdapter;
use MyParcelNL\Sdk\src\Model\MyParcelConsignment;
use MyParcelNL\Sdk\src\Model\MyParcelRequest;
use MyParcelNL\Sdk\src\Services\CollectionEncode;
use MyParcelNL\Sdk\src\Support\Collection;

/**
 * Stores all data to communicate with the MyParcel API
 *
 * Class MyParcelCollection
 */
class MyParcelCollection extends Collection
{
    const PREFIX_PDF_FILENAME = 'myparcel-label-';
    const DEFAULT_A4_POSITION = 1;

    /**
     * @var string
     */
    private $paper_size = 'A6';

    /**
     * The position of the label on the paper.
     * pattern: [1 - 4]
     * example: 1. (top-left)
     *          2. (top-right)
     *          3. (bottom-left)
     *          4. (bottom-right)
     *
     * @var string
     */
    private $label_position;

    /**
     * Link to download the PDF
     *
     * @var string
     */
    private $label_link;

    /**
     * Label in PDF format
     *
     * @var string
     */
    private $label_pdf;

    /**
     * @var string
     */
    private $user_agent = '';

    /**
     * @param bool $keepKeys
     *
     * @return MyParcelConsignment[]
     */
    public function getConsignments($keepKeys = true)
    {
        if ($keepKeys) {
            return $this->items;
        }

        return array_values($this->items);
    }

    /**
     * Get one consignment
     *
     * @return \MyParcelNL\Sdk\src\Model\MyParcelConsignment|null
     * @throws \Exception
     */
    public function getOneConsignment()
    {
        if ($this->count() > 1) {
            throw new \Exception('Can\'t run getOneConsignment(): Multiple items found');
        }

        return $this->first();
    }

    /**
     * @param string $id
     *
     * @return MyParcelCollection
     */
    public function getConsignmentsByReferenceId($id)
    {
        return $this->where('reference_identifier', $id);
    }

    /**
     * This is deprecated because there may be multiple consignments with the same reference id
     *
     * @deprecated Use getConsignmentsByReferenceId instead
     * @param $id
     * @return mixed
     */
    public function getConsignmentByReferenceId($id)
    {
        return $this->getConsignmentsByReferenceId($id)->first();
    }

    /**
     * @param integer $id
     *
     * @return MyParcelConsignment
     */
    public function getConsignmentByApiId($id)
    {
        return $this->where('myparcel_consignment_id', $id)->first();
    }

    /**
     * @return string
     *
     * this is used by third parties to access the label_pdf variable.
     */
    public function getLabelPdf()
    {
        return $this->label_pdf;
    }

    /**
     * @return string
     */
    public function getLinkOfLabels()
    {
        return $this->label_link;
    }

    /**
     * @param MyParcelConsignment $consignment
     *
     * @return $this
     * @throws \Exception
     */
    public function addConsignment(MyParcelConsignment $consignment)
    {
        if ($consignment->getApiKey() === null) {
            throw new \Exception('First set the API key with setApiKey() before running addConsignment()');
        }

        $this->push($consignment);

        return $this;
    }

    /**
     * Create concepts in MyParcel
     *
     * @return  $this
     * @throws  \Exception
     */
    public function createConcepts()
    {
        $this->addMissingReferenceId();

        /* @var $consignments MyParcelCollection */
        foreach ($this->where('myparcel_consignment_id', null)->groupBy('api_key') as $apiKey => $consignments) {

            $data = (new CollectionEncode($consignments))->encode();
            $request = (new MyParcelRequest())
                ->setUserAgent($this->getUserAgent())
                ->setRequestParameters(
                    $apiKey,
                    $data,
                    MyParcelRequest::REQUEST_HEADER_SHIPMENT
                )
                ->sendRequest();

            foreach ($request->getResult('data.ids') as $responseShipment) {
                /** @var MyParcelConsignment $consignment */
                $consignment = $this->getConsignmentsByReferenceId($responseShipment['reference_identifier'])->first();
                $consignment->setMyParcelConsignmentId($responseShipment['id']);
            }
        }

        return $this;
    }

    /**
     * Delete concepts in MyParcel
     *
     * @return  $this
     * @throws  \Exception
     */
    public function deleteConcepts()
    {
        /* @var $consignments MyParcelConsignment[] */
        foreach ($this->groupBy('api_key')->where('myparcel_consignment_id', '!=', null) as $key => $consignments) {
            foreach ($consignments as $consignment) {
                (new MyParcelRequest())
                    ->setUserAgent($this->getUserAgent())
                    ->setRequestParameters(
                        $key,
                        $consignment->getMyParcelConsignmentId(),
                        MyParcelRequest::REQUEST_HEADER_DELETE
                    )
                    ->sendRequest('DELETE');
            }
        }

        return $this;
    }

    /**
     * Get all current data
     *
     * Set id and run this function to update all the information about this shipment
     *
     * @param int $size
     *
     * @return $this
     * @throws \Exception
     */
    public function setLatestData($size = 300)
    {
        $myParcelRequest = new MyParcelRequest();
        $params = $myParcelRequest->getLatestDataParams($size, $this, $key);

        $request = $myParcelRequest
            ->setUserAgent($this->getUserAgent())
            ->setRequestParameters(
                $key,
                $params,
                MyParcelRequest::REQUEST_HEADER_RETRIEVE_SHIPMENT
            )
            ->sendRequest('GET');

        if ($request->getResult() === null) {
            throw new \Exception('Unable to transport data to/from MyParcel');
        }

        $result = $request->getResult('data.shipments');
        $newCollection = $this->getNewCollectionFromResult($result);

        $this->items = $newCollection->items;

        return $this;
    }

    /**
     * Get all the information about the last created shipments
     *
     * @param $key
     * @param int $size
     *
     * @return $this
     * @throws \Exception
     */
    public function setLatestDataWithoutIds($key, $size = 300)
    {
        $params = '?size=' . $size;

        $request = (new MyParcelRequest())
            ->setUserAgent($this->getUserAgent())
            ->setRequestParameters(
                $key,
                $params,
                MyParcelRequest::REQUEST_HEADER_RETRIEVE_SHIPMENT
            )
            ->sendRequest('GET');

        if ($request->getResult() === null) {
            throw new \Exception('Unable to transport data to MyParcel.');
        }

        foreach ($request->getResult()['data']['shipments'] as $shipment) {
            $consignmentAdapter = new ConsignmentAdapter($shipment, $key);
            $this->addConsignment($consignmentAdapter->getConsignment());
        }

        return $this;
    }

    /**
     * Get link of labels
     *
     * @param integer $positions The position of the label on an A4 sheet. Set to false to create an A6 sheet.
     *                                  You can specify multiple positions by using an array. E.g. [2,3,4]. If you do
     *                                  not specify an array, but specify a number, the following labels will fill the
     *                                  ascending positions. Positioning is only applied on the first page with labels.
     *                                  All subsequent pages will use the default positioning [1,2,3,4].
     *
     * @return $this
     * @throws \Exception
     */
    public function setLinkOfLabels($positions = self::DEFAULT_A4_POSITION)
    {
        /** If $positions is not false, set paper size to A4 */
        $this
            ->createConcepts()
            ->setLabelFormat($positions);

        $conceptIds = $this->getConsignmentIds($key);

        if ($key) {
            $request = (new MyParcelRequest())
                ->setUserAgent($this->getUserAgent())
                ->setRequestParameters(
                    $key,
                    implode(';', $conceptIds) . '/' . $this->getRequestBody(),
                    MyParcelRequest::REQUEST_HEADER_RETRIEVE_LABEL_LINK
                )
                ->sendRequest('GET', MyParcelRequest::REQUEST_TYPE_RETRIEVE_LABEL);

            $this->label_link = MyParcelRequest::REQUEST_URL . $request->getResult('data.pdfs.url');
        }

        $this->setLatestData();

        return $this;
    }

    /**
     * Receive label PDF
     *
     * After setPdfOfLabels() apiId and barcode is present
     *
     * @param integer $positions The position of the label on an A4 sheet. You can specify multiple positions by
     *                                  using an array. E.g. [2,3,4]. If you do not specify an array, but specify a
     *                                  number, the following labels will fill the ascending positions. Positioning is
     *                                  only applied on the first page with labels. All subsequent pages will use the
     *                                  default positioning [1,2,3,4].
     *
     * @return $this
     * @throws \Exception
     */
    public function setPdfOfLabels($positions = self::DEFAULT_A4_POSITION)
    {
        /** If $positions is not false, set paper size to A4 */
        $this
            ->createConcepts()
            ->setLabelFormat($positions);
        $conceptIds = $this->getConsignmentIds($key);

        if ($key) {
            $request = (new MyParcelRequest())
                ->setUserAgent($this->getUserAgent())
                ->setRequestParameters(
                    $key,
                    implode(';', $conceptIds) . '/' . $this->getRequestBody(),
                    MyParcelRequest::REQUEST_HEADER_RETRIEVE_LABEL_PDF
                )
                ->sendRequest('GET', MyParcelRequest::REQUEST_TYPE_RETRIEVE_LABEL);

            $this->label_pdf = $request->getResult();
        }
        $this->setLatestData();

        return $this;
    }

    /**
     * Download labels
     *
     * @param bool $inline_download
     *
     * @return void
     * @throws \Exception
     */
    public function downloadPdfOfLabels($inline_download = false)
    {
        if ($this->label_pdf == null) {
            throw new \Exception('First set label_pdf key with setPdfOfLabels() before running downloadPdfOfLabels()');
        }

        header('Content-Type: application/pdf');
        header('Content-Length: ' . strlen($this->label_pdf));
        header('Content-disposition: ' . ($inline_download === true ? "inline" : "attachment") . '; filename="' . self::PREFIX_PDF_FILENAME . gmdate('Y-M-d H-i-s') . '.pdf"');
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: public');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        echo $this->label_pdf;
        exit;
    }

    /**
     * Send return label to customer. The customer can pay and download the label.
     *
     * @throws \Exception
     * @return $this
     */
    public function sendReturnLabelMails()
    {
        $parentConsignment = $this->getConsignments(false)[0];

        $apiKey = $parentConsignment->getApiKey();
        $data = $this->apiEncodeReturnShipment($parentConsignment);

        $request = (new MyParcelRequest())
            ->setUserAgent($this->getUserAgent())
            ->setRequestParameters(
                $apiKey,
                $data,
                MyParcelRequest::REQUEST_HEADER_RETURN
            )
            ->sendRequest('POST');

        $result = $request->getResult();

        if ($result === null) {
            throw new \Exception('Unable to connect to MyParcel.');
        }

        if (empty($result['data']['ids'][0]['id']) ||
            (int) $result['data']['ids'][0]['id'] < 1
        ) {
            throw new \Exception('Can\'t send retour label to customer. Please create an issue on GitHub or contact MyParcel; support@myparcel.nl. Note this request body: ' . $data);
        }

        return $this;
    }

    /**
     * Get all consignment ids
     *
     * @internal
     * @param $key
     *
     * @return array
     */
    public function getConsignmentIds(&$key)
    {
        $conceptIds = [];

        /** @var MyParcelConsignment $consignment */
        foreach ($this->where('myparcel_consignment_id', '!=', null) as $consignment) {
            $conceptIds[] = $consignment->getMyParcelConsignmentId();
            $key = $consignment->getApiKey();
        }

        if (empty($conceptIds)) {
            return null;
        }

        return $conceptIds;
    }

    /**
     * Set label format settings        The position of the label on an A4 sheet. You can specify multiple positions by
     *                                  using an array. E.g. [2,3,4]. If you do not specify an array, but specify a
     *                                  number, the following labels will fill the ascending positions. Positioning is
     *                                  only applied on the first page with labels. All subsequent pages will use the
     *                                  default positioning [1,2,3,4].
     *
     * @param integer|array|null $positions
     *
     * @return $this
     */
    private function setLabelFormat($positions)
    {
        /** If $positions is not false, set paper size to A4 */
        if (is_numeric($positions)) {
            /** Generating positions for A4 paper */
            $this->paper_size = 'A4';
            $this->label_position = LabelHelper::getPositions($positions);
        } elseif (is_array($positions)) {
            /** Set positions for A4 paper */
            $this->paper_size = 'A4';
            $this->label_position = implode(';', $positions);
        } else {
            /** Set paper size to A6 */
            $this->paper_size = 'A6';
            $this->label_position = null;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRequestBody()
    {
        $body = $this->paper_size == 'A4' ? '?format=A4&positions=' . $this->label_position : '?format=A6';

        return $body;
    }

    /**
     * @return string
     */
    public function getUserAgent()
    {
        return $this->user_agent;
    }

    /**
     * @param string $platform
     * @param string $version
     * @internal param string $user_agent
     * @return $this
     */
    public function setUserAgent($platform, $version = null)
    {
        $this->user_agent = 'MyParcel-' . $platform;
        if ($version !== null) {
            $this->user_agent .= '/' . str_replace('v', '', $version);
        }

        return $this;
    }

    /**
     * Clear this collection
     */
    public function clearConsignmentsCollection() {
        $this->items = [];
    }

    /**
     * Encode ReturnShipment to send to MyParcel
     *
     * @param MyParcelConsignment $consignment
     *
     * @return string
     */
    private function apiEncodeReturnShipment($consignment)
    {
        $data = [];
        $shipment = [
            'parent' => $consignment->getMyParcelConsignmentId(),
            'carrier' => 1,
            'email' => $consignment->getEmail(),
            'name' => $consignment->getPerson(),
        ];

        $data['data']['return_shipments'][] = $shipment;

        return json_encode($data);
    }

    /**
     * @param $result
     * @return MyParcelCollection
     * @throws \Exception
     */
    private function getNewCollectionFromResult($result)
    {
        $newCollection = new MyParcelCollection();
        foreach ($result as $shipment) {

            /** @var Collection|MyParcelConsignment[] $consignments */
            $consignments = $this->where('myparcel_consignment_id', $shipment['id']);

            if ($consignments->isEmpty()) {
                $consignments = $this->getConsignmentsByReferenceId($shipment['reference_identifier']);
            }

            $consignmentAdapter = new ConsignmentAdapter($shipment, $consignments->first()->getApiKey());

            $newCollection->addConsignment($consignmentAdapter->getConsignment());
        }

        return $newCollection;
    }

    private function addMissingReferenceId()
    {
        $this->transform(function($consignment) {
            /** @var MyParcelConsignment $consignment */
            if (null === $consignment->getReferenceId()) {
                $consignment->setReferenceId('random_' . uniqid());
            }

            return $consignment;
        });
    }
}
