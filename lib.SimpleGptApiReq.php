<?php


//==============================================================================
class SimpleGptApiReqRsp
{
	public false|string    $Raw = '';
	public \stdClass|array $RawDecoded;
	public string          $Data = '';
	public bool            $Err  = false;

	public function __construct() {
		$this->Reset();
	}

	public function Reset() {
		$this->Raw  = '';
		$this->RawDecoded = new \stdClass;
		$this->Data = '';
		$this->Err = false;
	}

	public function Cost() : float {
		// @TODO
	}
}


//==============================================================================
class SimpleGptApiReq
{
	private string $provider = ''; // Anthropic, Gemini, Ollama, OpenAI
	private string $reqUrl   = '';

	private SimpleGptApiReqRsp $rsp;
	private CurlHandle   $curl;

	//============================================================================
	public function __construct(
		private string $Model,
		private string $ApiKey,
		private string $Prompt,

		// Optional
		private string $OllamaUrl   = '',
		private float  $Temperature = 0.5,
		private int    $MaxTokens   = 4096,
		// private string $SysContext = ''; // don't configure until we need it?
	) {
		$this->rsp  = new SimpleGptApiReqRsp;
		$this->curl = curl_init();

		if (!empty($this->OllamaUrl)) {
			$this->setOllamaApiRootUrl();
		}
	}

	//----------------------------------------------------------------------------
	public function GetRsp() : SimpleGptApiReqRsp {
		return $this->rsp;
	}

	//----------------------------------------------------------------------------
	// if we need to debug the connection/request/response/etc...
	public function GetCurl() : CurlHandle{
		return $this->curl;
	}

	//----------------------------------------------------------------------------
	public function setModel(string $model) {
		$this->Model = $model;
	}
	public function setApiKey(string $api_key) {
		$this->ApiKey = $api_key;
	}
	public function setPrompt(string $prompt) {
		$this->Prompt = $prompt;
	}
	public function setMaxTokens(string $max_tokens) {
		$this->MaxTokens = $max_tokens;
	}
	public function setTemperature(string $temperature) {
		$this->Temperature = $temperature;
	}

	//----------------------------------------------------------------------------
	public function Exec() : SimpleGptApiReqRsp
	{
		$this->rsp->Reset();

		if (false == $this->init()) {
			$this->rsp->Err = true;
			return $this->rsp;
		}

		$this->curl = curl_init();
		switch ($this->provider) {
			case 'Anthropic':
				$this->execAnthropic();
				break;
			case 'Gemini':
				$this->execGemini();
				break;
			case 'Ollama':
				$this->execOllama();
				break;
			case 'OpenAI':
				$this->execOpenAI();
				break;
			default:
				break;
		}

		// don't close, in case we need it for debugging GetCurl()
		// curl_close($this->curl); php8+, does nothing. before php8, deleted.

		return $this->rsp;
	}

	//----------------------------------------------------------------------------
	private function setOllamaApiRootUrl()
	{
		if (   !str_starts_with($this->OllamaUrl, 'http://')
	      && !str_starts_with($this->OllamaUrl, 'https://')) {
			$this->OllamaUrl = '';
		}
		elseif (str_ends_with($this->OllamaUrl, '/api/chat')) {
			// ... do nothing
		}
		elseif (str_ends_with($this->OllamaUrl, '/api/chat/')) {
			// will not work with trailing slash
			$this->OllamaUrl = rtrim($this->OllamaUrl, '/');
		}
		else {
			$this->OllamaUrl = rtrim($this->OllamaUrl, '/').'/api/chat';
		}
	}

	//----------------------------------------------------------------------------
	private function init() : false|string
	{
		$this->reqUrl = '';

		//------------------------------------------------------------
		switch ($this->Model)
		{
			//-----------------------------------
			// Anthropic / Claude
			// https://docs.anthropic.com/en/docs/about-claude/models
			case 'claude-3-5-sonnet-20240620':
			case 'claude-3-opus-20240229':
			case 'claude-3-sonnet-20240229':
			case 'claude-3-haiku-20240307':
				$this->provider = 'Anthropic';
				$this->reqUrl = 'https://api.anthropic.com/v1/messages';
				break;

			//-----------------------------------
			// Google / Gemini
			// https://ai.google.dev/gemini-api/docs/models/gemini
			case 'gemini-1.5-flash':
			case 'gemini-1.5-pro':
			case 'gemini-1.5-pro-exp-0827':
			case 'gemini-1.0-pro':
			case 'text-embedding-004':
				if (empty($this->ApiKey) || empty($this->Model)) { break; }
				$this->provider = 'Gemini';
				$this->reqUrl = "https://generativelanguage.googleapis.com/v1beta/models/$this->Model:generateContent?key=$this->ApiKey";
				break;

			//-----------------------------------
			// ollama
			// @TODO: configure host

			//-----------------------------------
			// OpenAI / ChatGPT
			// https://platform.openai.com/docs/models
			case 'gpt-4o':
			case 'gpt-4o-2024-05-13':
			case 'gpt-4o-2024-08-06':
			case 'chatgpt-4o-latest':
			case 'gpt-4o-mini':
			case 'gpt-4o-mini-2024-07-18':
			case 'gpt-4-turbo':
			case 'gpt-4-turbo-2024-04-09':
			case 'gpt-4-turbo-preview':
			case 'gpt-4-0125-preview':
			case 'gpt-4-1106-preview':
			case 'gpt-4':
			case 'gpt-4-0613':
			case 'gpt-4-0314':
			case 'gpt-3.5-turbo-0125':
			case 'gpt-3.5-turbo':
			case 'gpt-3.5-turbo-1106':
			case 'gpt-3.5-turbo-instruct':
				$this->provider = 'OpenAI';
				$this->reqUrl = 'https://api.openai.com/v1/chat/completions';
				break;

			//-----------------------------------
			default:
				// if we have an ollama url/request
				if (!empty($this->OllamaUrl)) {
					$this->provider = 'Ollama';
					$this->reqUrl   = $this->OllamaUrl;
				}
				break;
		}

		//------------------------------------------------------------
		if (!empty($this->reqUrl)) {
			return $this->reqUrl;
		} else {
			return false;
		}
	}

	//----------------------------------------------------------------------------
	public function IsValidModel() {
		if (false === $this->getReqUrl()) {
			return false;
		}
		return true;
	}

	//----------------------------------------------------------------------------
	private function execAnthropic() {
		$post_data = [
			'model'       => $this->Model,
			'max_tokens'  => $this->MaxTokens,
			'temperature' => $this->Temperature,
			'stream'      => false,
		  "messages" => [
		  	[ "role" => "user", "content" => $this->Prompt]
		  ]
		];
		$post_fields = json_encode($post_data);

		curl_setopt($this->curl, CURLOPT_URL, $this->reqUrl);
		curl_setopt($this->curl, CURLOPT_POST, true);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $post_fields);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'anthropic-version: 2023-06-01',
			'x-api-key: ' . $this->ApiKey,
		]);

		$this->rsp->Raw        = curl_exec($this->curl);
		$this->rsp->RawDecoded = json_decode($this->rsp->Raw, true);
		if (isset($this->rsp->RawDecoded['content'][0]['text'])) {
			$this->rsp->Data = $this->rsp->RawDecoded['content'][0]['text'];
		} else {
			$this->rsp->Err = true;
		}
	}

	//----------------------------------------------------------------------------
	private function execGemini() {
		$post_data = [
		  "contents" => [
		  	"parts" => [
		  		[ "text" => $this->Prompt]
			  ]
		  ]
		];
		$post_fields = json_encode($post_data);

		curl_setopt($this->curl, CURLOPT_URL, $this->reqUrl);
		curl_setopt($this->curl, CURLOPT_POST, true);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $post_fields);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
		]);

		$this->rsp->Raw        = curl_exec($this->curl);
		$this->rsp->RawDecoded = json_decode($this->rsp->Raw, true);
		if (isset($this->rsp->RawDecoded['candidates'][0]['content']['parts'][0]['text'])) {
			$this->rsp->Data = $this->rsp->RawDecoded['candidates'][0]['content']['parts'][0]['text'];
		} else {
			$this->rsp->Err = true;
		}
	}

	//----------------------------------------------------------------------------
	private function execOllama() {
		$post_data = [
			"model"       => $this->Model,
			// "max_tokens"  => $this->MaxTokens, // not used in ollama
		  "options" => [
		    "temperature" => $this->Temperature
		  ],
			'stream'      => false,
			"messages"    => [
				[	"role" => "system", "content" => '', ],
				[	"role" => "user",   "content" => $this->Prompt, ]
			],
		];
		$post_fields = json_encode($post_data);

		curl_setopt($this->curl, CURLOPT_URL, $this->reqUrl);
		curl_setopt($this->curl, CURLOPT_POST, true);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $post_fields);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Authorization: Bearer ' . $this->ApiKey,
		]);

		$this->rsp->Raw        = curl_exec($this->curl);
		$this->rsp->RawDecoded = json_decode($this->rsp->Raw, true);
		if (isset($this->rsp->RawDecoded['choices'][0]['message']['content'])) {
			$this->rsp->Data = $this->rsp->RawDecoded['choices'][0]['message']['content'];
		} else {
			$this->rsp->Err = true;
		}
	}

	//----------------------------------------------------------------------------
	private function execOpenAI() {
		$post_data = [
			"model"       => $this->Model,
			"max_tokens"  => $this->MaxTokens,
			"temperature" => $this->Temperature,
			'stream'      => false,
			"messages"    => [
				[	"role" => "user", "content" => $this->Prompt, ]
			],
		];
		$post_fields = json_encode($post_data);

		curl_setopt($this->curl, CURLOPT_URL, $this->reqUrl);
		curl_setopt($this->curl, CURLOPT_POST, true);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $post_fields);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Authorization: Bearer ' . $this->ApiKey,
		]);

		$this->rsp->Raw        = curl_exec($this->curl);
		$this->rsp->RawDecoded = json_decode($this->rsp->Raw, true);
		if (isset($this->rsp->RawDecoded['choices'][0]['message']['content'])) {
			$this->rsp->Data = $this->rsp->RawDecoded['choices'][0]['message']['content'];
		} else {
			$this->rsp->Err = true;
		}
	}

}
