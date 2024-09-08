# SimpleGptApiReq

Single file PHP + Curl class to send a single chat request to OpenAI, Anthropic, Gemini, or ollama.

Use this if you're interested in a single, lightweight solution for querying the major GPT APIs. Or if you just want to understand better how the requests and responses happen.

There are still some bugs to work out. But it's the quickest and easiest solution that exists if you want to start using the APIs.

```
<?php
require_once(__DIR__.'/lib.SimpleGptApiReq.php');

$req = new SimpleGptApiReq(
	'model-name',
	'api key',
	'your gpt prompt'
);

$rsp = $req->Exec();
print_r($rsp);
```