<?php
/**
 * Class FacebookMessageResponse
 * 
 * @package Model
 */
class FacebookMessageResponse 
{
	private $received;
	
	/**
	 * @var array
	 */
	private $response;

	/**
	 * FacebookMessageResponse constructor.
	 *
	 * @param string|FacebookMessageReceive $receive
	 */
	public function __construct() 
	{
		$this->received = new FacebookMessageReceive;
	}

	/**
	 * @param string $type Type can be REGULAR, SILENT_PUSH or NO_PUSH
	 */
	public function setNotificationType($type = 'REGULAR') 
	{
		$this->response['notification_type'] = $type;
	}

	public function getFacebookMessageReceived() 
	{
		return $this->received;
	}

	/**
	 * Choose userid to send message, if is null, userid = last userid that send message
	 *
	 * @param string $userid
	 */
	public function sendTo($userid = '') 
	{
		if ( ! empty( $userid ) ) 
		{
			$this->response['recipient'] = array(
				"id" => $userid
			);
		} 
		else 
		{
			if ( $this->received->getSender() ) 
			{
				$sender                      = $this->received->getSender();
				$this->response['recipient'] = array(
					"id" => $sender
				);
			}
		}
	}
    
    public function updateWelcome( $node )
    {
        $end_point = 'https://graph.facebook.com/v2.6/' . PAGE_ID . '/thread_settings?access_token=' . PAGE_ACCESS_TOKEN;
        
        $message = $this->normalizeNode( $node );
   
        $response = wp_remote_post( $end_point, array(
            'timeout' => 120,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers'     => array(
                'Content-Type' => 'application/json'
            ),
            'body'        => array(
            	'setting_type' => 'call_to_actions',
            	'thread_state' => 'new_thread',
            	'call_to_actions' => array(
            		array(
				      	'message' => $message
				    )
            	)
            )
        ) );

        // print_r($response);
    }

    /**
     * Response to user message or action
     * 
     * @param  Array $node Node which contain ask => response
     * @param  string $notification_type Either REGULAR, SILENT_PUSH or NO_PUSH
     * 
     * @return void
     */
    public function response( $node, $notification_type = '' )
    {
    	$node_content = array(
			'recipient' => array(
				'id' => $this->received->getSender()
			)
		);

    	$node_content['message'] = $this->normalizeNode( $node );

    	if ( in_array($notification_type, array( 'REGULAR', 'SILENT_PUSH', 'NO_PUSH' ) ) )
    		$node_content['notification_type'] = $notification_type;

		$response = wp_remote_post( "https://graph.facebook.com/v2.6/me/messages?access_token=" . PAGE_ACCESS_TOKEN, array(
            'timeout' => 120,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers'     => array(
                'Content-Type' => 'application/json'
            ),
            'body'        => $node_content
        ) );
        
        // if ( is_wp_error( $response ) ) {
        //     $error_message = $response->get_error_message();
        //     file_put_contents('error.log', serialize($error_message));
        // } else {
        //    // 
        // }
    }

    public function normalizeNode( $node )
    {

    	if ( $node['type'] == 'image' ) {
    		return array( 
    			'attachment' => array(
					'type'    => 'image',
					'payload' => array(
						'url' => $node['content']
				)
			) );
    	}

    	if ( $node['type'] == 'button' )
    	{
    		return array(
				'attachment' => array(
					'type' 		=> 'template',
					'payload' 	=> array(
						'template_type' => 'button',
						'text'      	=> $node['content']['text'],
						'buttons'   	=> $node['content']['buttons']
					)
			) );
    	}

    	if ( $node['type'] == 'generic' )
    	{
    		return array(
				'attachment' => array(
					'type' 		=> 'template',
					'payload' 	=> array(
						'template_type' => 'generic',
						'elements' 		=> $node['content']
					)
				)
			);
    	}

    	if ( $node['type'] == 'receipt' )
    	{
    		$timestamp = empty( $node['content']['timestamp'] ) ? time() : $node['content']['timestamp'];
		
			return array(
				'attachment' 	=> array(
					'type' 		=> 'template',
					'payload' 	=> array(
						'template_type' 	=> 'receipt',
						'recipient_name' 	=> $node['content']['name'],
						'order_number' 		=> $node['content']['order_number'],
						'currency' 			=> $node['content']['currency'],
						'payment_method' 	=> $node['content']['payment_method'],
						'order_url' 		=> $node['content']['order_url'],
						'timestamp' 		=> $timestamp,
						'elements' 			=> $node['content']['elements'],
						'address' 			=> $node['content']['address'],
						'summary' 			=> $node['content']['summary'],
						'adjustments' 		=> $node['content']['adjustments']
					)
				)
			);
    	}

    	return array(
    		'text' => $node['content']
    	);
    }
}