# SimpleGptApiReq

Single file PHP + Curl class to send a single chat request to OpenAI, Anthropic, Gemini, or ollama.

Use this if you're interested in a single, lightweight solution for querying the major GPT APIs. Or if you just want to understand better how the requests and responses happen.

There are still some bugs to work out. But it's the quickest and easiest solution that exists if you want to start using the APIs.

See example.php for a working example, which just requires you adding your API key(s). And if you want to use ollama, also adding the ollama root url.

```php
require_once(__DIR__.'/lib.SimpleGptApiReq.php');

$req = new SimpleGptApiReq(
	'model-name',
	'api key',
	'your gpt prompt'
);

$rsp = $req->Exec(); // returns new SimpleGptApiReqRsp()
print_r($rsp);

$resultText = $rsp->Data;
```

To use ollama, just add your ollama api root url after 'your gpt prompt' above. eg:

```php
$req = new SimpleGptApiReq(
	'ollama-model-name',
	'ollama api key',
	'your gpt prompt',
	'ollama root url'
);

$rsp = $req->Exec(); // returns new SimpleGptApiReqRsp()
print_r($rsp);

$resultText = $rsp->Data;
```

