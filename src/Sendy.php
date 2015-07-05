<?php namespace Hocza\Sendy;

use Illuminate\Support\Facades\Config;

class Sendy {

	protected $installation_url;
	protected $api_key;
	protected $list_id;

	public function __construct()
	{
		//error checking
		$this->list_id = Config::get('sendy::list_id');
		$this->installation_url = Config::get('sendy::installation_url');
		$this->api_key = Config::get('sendy::api_key');
		
		if (!isset($this->list_id)) {
			throw new \Exception("[list_id] is not set", 1);
		}
		
		if (!isset($this->installation_url)) {
			throw new \Exception("[installation_url] is not set", 1);
		}
		
		if (!isset($this->api_key)) {
			throw new \Exception("[api_key] is not set", 1);
		}
	}

	public function subscribe(array $values)
    {
		$type = 'subscribe';
		//Send the subscribe
		$result = strval($this->buildAndSend($type, $values));
		//Handle results
		switch ($result) {
			case '1':
				return array(
					'status' => true,
					'message' => 'Subscribed'
					);
				break;
			case 'Already subscribed.':
				return array(
					'status' => true,
					'message' => 'Already subscribed.'
					);
				break;
			default:
				return array(
					'status' => false,
					'message' => $result
					);
				break;
		}
    }
    public function unsubscribe($email)
    {
		$type = 'unsubscribe';
		//Send the unsubscribe
		$result = strval($this->buildAndSend($type, array('email' => $email)));
		//Handle results
		switch ($result) {
			case '1':
				return array(
					'status' => true,
					'message' => 'Unsubscribed'
					);
				break;
			
			default:
				return array(
					'status' => false,
					'message' => $result
					);
				break;
		}
    }

    public function status($email)
    {
		$type = 'api/subscribers/subscription-status.php';
		//Send the status request
		$result = strval($this->buildAndSend($type, array('email' => $email)));
		//Simply returning the result
		return $result;
    }

    public function count()
    {
		$type = 'api/subscribers/active-subscriber-count.php';
		//Send the status request
		$result = strval($this->buildAndSend($type, array()));
		//Simply returning the result
		return $result;
    }

    public function setListId($list_id)
    {
    	$this->list_id = $list_id;
    	return $this;
    }

    /**
	 * Create a campaign based on the input params. See API (https://sendy.co/api#4) for parameters.
	 * Bug: The API doesn't save the list_ids passed to Sendy.
	 * 
	 * @param $campaignOptions
	 * @param $campaignContent
	 * @param bool $sendCampaign Set this to true to send the campaign
	 * @return string
	 * @throws \Exception
	 */
    public function createCampaign($campaignOptions, $campaignContent, $sendCampaign = false)
	{
		$type = '/api/campaigns/create.php';

		if (empty($campaignOptions['from_name'])) 	throw new \Exception("From Name is not set", 1);
		if (empty($campaignOptions['from_email'])) 	throw new \Exception("From Email is not set", 1);
		if (empty($campaignOptions['reply_to'])) 	throw new \Exception("Reply To address is not set", 1);
		if (empty($campaignOptions['subject'])) 	throw new \Exception("Subject is not set", 1);

		// 'plain_text' field can be included, but optional
		if (empty($campaignContent['html_text'])) 	throw new \Exception("Campaign Content (HTML) is not set", 1);

		if ($sendCampaign) {
			if (empty($campaignOptions['brand_id'])) throw new \Exception("Brand ID should be set for Draft campaigns", 1);
		}

		// list IDs can be single or comma separated values
		if (empty($campaignOptions['list_ids'])) $campaignOptions['list_ids'] = $this->list_id;

		// should we send the campaign (1) or save as Draft (0)
		$campaignOptions['send_campaign'] = ($sendCampaign)? 1: 0;

		$result = strval($this->buildAndSend($type, array_merge($campaignOptions, $campaignContent)));

		return $result;
	}

	private function buildAndSend($type, array $values)
	{
		$return_options = array(
			'list' => $this->list_id,
			//Passing list_id too, because old API calls use list, new ones use list_id 
			'list_id' => $this->list_id, # ¯\_(ツ)_/¯
			'api_key' => $this->api_key,
			'boolean' => 'true'
		);
		//Merge the passed in values with the options for return
		$content = array_merge($values, $return_options);
		//build a query using the $content
		$post_data = http_build_query($content);
		$ch = curl_init($this->installation_url .'/'. $type);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded"));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

}