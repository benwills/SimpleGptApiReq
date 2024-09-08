<?php

require_once(__DIR__.'/lib.SimpleGptApiReq.php');

$api_key_anthropic = 'sk-ant-api03-...';
$api_key_gemini    = '...';
$api_key_openai    = 'sk-proj-...';
$api_key_ollama    = 'sk-...';

$ollama_url = 'http://localhost:11434/';

$reqs = [
	[	'model' => 'claude-3-haiku-20240307',
		'key'   => $api_key_anthropic,
	],
	[	'model' => 'gemini-1.5-pro-exp-0827',
		'key'   => $api_key_gemini,
	],
	[	'model' => 'gpt-4o-mini',
		'key'   => $api_key_openai,
	],

	[	'model'     => 'gemma2',
		'key'       => $api_key_ollama,
		'ollamaUrl' => $ollama_url,
	],
	[	'model'     => 'llama3',
		'key'       => $api_key_ollama,
		'ollamaUrl' => $ollama_url,
	],
	[	'model'     => 'mistral',
		'key'       => $api_key_ollama,
		'ollamaUrl' => $ollama_url,
	],
	[	'model'     => 'mistral-nemo',
		'key'       => $api_key_ollama,
		'ollamaUrl' => $ollama_url,
	],
	[	'model'     => 'qwen2',
		'key'       => $api_key_ollama,
		'ollamaUrl' => $ollama_url,
	],
];

$prompt = 'Give me a list of 10 random states in the USA.';

foreach ($reqs as $req_cfg)
{
	echo "\n\n===============================================\n";

	$req = new SimpleGptApiReq(
		$req_cfg['model'],
		$req_cfg['key'],
		$prompt,
		(isset($req_cfg['ollamaUrl']) ? $req_cfg['ollamaUrl'] : '')
	);

	$rsp = $req->Exec();
	if ($rsp->Err) {
		echo "ERROR\n\n";
		print_r($req->GetRsp());
	} else {
		print_r($rsp);
	}

}


