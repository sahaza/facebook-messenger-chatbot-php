<?php
/**
 * Messenger Bot class
 *
 * @version 1.1
 */
class FacebookMessengerBot
{
	public $args = array(

		'text' => array(),

		'payload' => array(),
	
		'welcome' => array(),

		'default' => array()
	);

	protected $messenger;

	protected $messenger_response;

	public function __construct( $args = array() )
	{
		$this->messenger 			= new FacebookMessageReceive;

		$this->messenger_response 	= new FacebookMessageResponse;

		if ( ! empty( $args ) )
		{
			// We still supports old syntax
			if ( isset( $args['default'] ) || isset( $args['text'] ) )
				$this->args = $args;
			else
				$this->answers( $args );
		}
	}

	public function get_userid() 
	{
		return $this->messenger->getSender();
	}

	public function get_time() 
	{
		return $this->messenger->getTime();
	}

	public function get_id() 
	{
		return $this->messenger->getID();
	}

	public function answers( $asks, $answers = null )
	{
		if ( is_string( $asks ) )
		{
			$node_type = 'text';

			foreach (array('payload', 'default', 'welcome') as $type )
			{
				if ( strpos($asks, $type . ':') !== false) {

					$node_type = $type;
					
					$asks = ltrim( $asks, $type . ':' );
				}
			}

			if ( ! isset( $this->args[$node_type][$asks] ) && in_array( $node_type, array( 'text', 'payload' ) ) )
				$this->args[$node_type][$asks] = array();
			
			if ( is_string( $answers ) )
			{				
				$answers = array(
					'type' => 'text',
					'content' => $answers
				);
			}

			if ( is_array( $answers ) )
			{ 
				// If 1 level
				if ( isset( $answers['type'] ) )
				{
					if ( in_array( $node_type, array( 'text', 'payload' ) ) )
						$this->args[$node_type][$asks][] = $answers;
					
					if ( $node_type === 'default' )
						$this->args['default'][] = $answers;

					if ( $node_type === 'welcome' )
						$this->args['welcome'] = $answers;
				}
				else
				{
					foreach ( $answers as $answer )
					{
						
						// Normalize templates
						foreach ( array( 'button', 'receipt', 'generic', 'image' ) as $template )
						{
							if ( isset( $answers[$template] ) && is_array( $answer ) )
							{
								$answer = array(
									'type' 		=> $template,
									'content' 	=> $answers[$template]
								);
							}
						}
						
						if ( is_string( $answer ) )
						{
							$answer = array(
								'type' => 'text',
								'content' => $answer
							);
						}
						
						if ( is_array( $answer ) )
						{
						
							if ( in_array( $node_type, array( 'text', 'payload' ) ) )
								$this->args[$node_type][$asks][] = $answer;
							
							if ( $node_type === 'default' )
								$this->args['default'][] = $answer;

							if ( $node_type === 'welcome' )
								$this->args['welcome'] = $answer;
						}
					}
				}
			}
		}

		if ( is_array( $asks ) && is_null( $answers ) )
		{
			foreach ( $asks as $ask => $answers )
			{
				$this->answers( $ask, $answers );
			}
		}
	}

	public function run()
	{
		// If receive delivery message
		if ( $this->messenger->getDelivery() ) 
		{
			do_action( 'fmb_delivery' );
			
			return file_put_contents( "delivery.log", serialize( $this->messenger->getMessage() ) );
		}

		if ( isset( $_REQUEST['update_welcome'] ) )
		{
			$welcome = $this->args['welcome'];
			
			$this->messenger_response->updateWelcome( $welcome );
		}


		if ( ! empty( $this->messenger->getMessagingMessage()->text ) ) 
		{
			$ask = $this->messenger->getMessagingMessage()->text;

			// Todo: Change to preg_grep for better performance
			foreach ( $this->args['text'] as $pattern => $answers )
			{
				if ( ! $this->isMatch( $ask, $pattern ) )
					continue;

				foreach ( $answers as $answer )
				{
					$this->messenger_response->response($answer);
				}

				return;
			}
		}

		$payload = $this->messenger->getMessagingPostbackPayload();

		if ( ! empty( $payload ) )
		{            
			do_action( 'fmb_send' );

			do_action( 'fmb_send_payload' );
            
            if ( ! empty( $this->args['payload'][$payload] ) )
            {
                foreach( $this->args['payload'][$payload] as $answer )
                {
                    $this->messenger_response->response( $answer );
                }
                
                return;
            }
		}

		if ( ! empty( $this->args['default'] ) )
		{
			foreach ($this->args['default'] as $answer) 
			{
				$this->messenger_response->response($answer);
			}
		}

		return false;
	}

	private function isMatch( $user_text, $pattern )
	{
		$pattern = str_replace('%', "[\s\S]*", $pattern);
		
		return preg_match("/$pattern/i", $user_text );
	}
}