Exo: FaaS framework supporting externalized Actions and Triggers
================================================================

Inspired by the trends in Serverless / FaaS / Cloud Functions.

## Features:

* Provides a framework to build, call and test reusable, stateless, language agnostic functions (Exo Actions).
* A language agnostic YAML format to define the metadata of your Exo Actions, specifying their name, description, tags and detailed `input` and `output` schemas.
* Uses JSON Schema to validate every request, response, input and output.
* An HTTP end-point server to serve your Exo Actions (exo-server).
* A Console tool to help build, test and debug your Exo Actions.

## Project status

Exo is currently in an experimental phase, and some of the features are under construction.

## core-exo-actions:

Check out https://github.com/linkorb/core-exo-actions for a range of library of reusable common Exo actions.

To test it out:

    cp .env.dist .env
    edit .env # setup your EXO_ACTIONS path

    # List all available actions
    bin/exo action

    # Inspect a particular action (hello-php)
    bin/exo action hello-php -i greeting=Hello -i name=Alice

    # Run a particular action (hello-php) with specified input values
    bin/exo run hello-php -i greeting=Hello -i name=Alice

    # Handle a full JSON request
    bin/exo request < request.json # load request from stdin
    bin/exo request request.json # load request from file


## Custom actions

It's easy to implement your own actions, in your language of choice, usually in just a few lines of code.

Check out the core-exo-actions repository for a set of "hello world" examples in PHP, node.js and Bash (most other common languages supported too).

To use your actions in Exo (CLI, Worker, Server), simply add the path to your actions to the EXO_ACTIONS environment variable.

## Request/Response JSON

Exo transforms JSON requests into JSON responses.

### Requests

A request contains:

1. the action name: determines which action to run
2. an optional set of input variables (may be strings, integers, objects): passed as input to the action
3. an optional list of output mappings: applied on output variables

```json
{
    "action": "random-number",
    "input": {
        "min": "100",
        "max": "200"
    },
    "mapping": {
        "result": "surprise"
    }
}
```

You can execute this request (and pretty-print + color-code it using `jq`) like this:

    bin/exo request < request.json | jq

Response:

```json
{
    "status": "OK",
    "output": {
        "surprise": "123"
    }
}
```


### Handling a request

When Exo receives the request, it will

0. Find the action information (input/output variables)
0. Validate the full request against data/request.schema.json
0. Validate the input variables against the input JSON schema of the requested action
0. Execute the action
0. Validate the output variables againt the output JSON schema of the requested action
0. Optionally apply output variable mapping (renaming)
0. Validate the full response against data/response.schema.json
0. Return the response JSON

### Response

Example response:

```json
{
    "status": "OK",
    "output": {
        "sentence": "Hello, Joe"
    }
}
```

A response contains a `status` that contains either `OK` or `ERROR`.

If the action has any output variables, they are specified in the `output` object (optional mappings applied)

## Variables (URLs, Credentials, etc)

You can variables and secrets to your exo instance as environment variables. Make sure the environment variables are prefixed with `EXO__VARIABLE__`. For example:

    EXO__VARIABLE__MATTERMOST_URL=https://mattermost.example.com/hooks/xyz123abc

You can now use these variables in your input variables:

    ./bin/exo run mattermost-send-message -i url={{MATTERMOST_URL}} -i channel=@alice -i text=Hi

The variables are only accessible by the Exo instance, but any request can refer to them. This makes it easy to call actions from client applications (i.e. Camunda) without passing around hardcoded URLs and credentials in your client code, processes, etc.

## Workers

You can run Exo as a worker. In this mode, Exo waits for requests, executes them, and returns the responses.

To know where to find requests, you can specify the type of worker, and any options that worker needs to be instantiated.

Currently the [NATS](https://nats.io/) and [Camunda](https://camunda.com/) workers are available. In the future a Kafka, Rabbitmq, or any other type of worker can be implemented easily.

To run the worker, simply run:

    bin/exo worker


### NATS Worker

The Camunda worker requires the following environment variables:

* `EXO__WORKER__TYPE`: Worker implementation: `Nats`
* `EXO__WORKER__NATS__HOST`: Hostname of the NATS server, i.e. `nats.example.com`
* `EXO__WORKER__NATS__PORT`: Port number of the NATS server, i.e. `4222` (defaut)
* `EXO__WORKER__NATS__USERNAME`: Username to authenticate with, i.e. `exo`
* `EXO__WORKER__NATS__PASSWORD`: Password to authenticate with
* `EXO__WORKER__NATS__SSL__VERIFY_PEER`: Configure steam context SSL option `verify_peer` (defaults to `true`)

You can now publish requests onto the `exo:request` "subject".

Note that the worker expects the payload to be a gzipped JSON string representing a regular Exo request.

It will use the NATS request/response mechanism to respond to the request with a gzipped JSON string representing a regular Exo response.

To test you can use the included `nats-request` command to send a request (as JSON over STDIN or by providing a filename), and receive a response on STDOUT. This command uses the worker's NATS environment variables to setup the connection to the NATS server.

* `./bin/console nats-request < request.json` # load request from STDIN
* `./bin/console nats-request request.json` # load request from filename

### Camunda Worker

The Camunda worker requires the following environment variables:

* `EXO__WORKER__TYPE`: Worker implementation: `Camunda`
* `EXO__WORKER__CAMUNDA__URL`: Base URL for the Camunda REST API, i.e. `http://127.0.0.1:8888/engine-rest`
* `EXO__WORKER__CAMUNDA__USERNAME`: Username to authenticate with, i.e. `exo`
* `EXO__WORKER__CAMUNDA__PASSWORD`: Password to authenticate with

It is recommended to create a dedicated user for Exo. This way each task is performed by the appropriate user, ensuring that Camunda permissions and logging are correctly related to Exo.

Once the worker is running, you can now create "Service Tasks" in your processes that trigger Exo actions.

In the Camunda modeler, create a "Task" and use the wrench to turn it into a "Service Task".

In the Properties Panel, set the Implementation as "External", and enter a topic. The Topic should always be prefixed with `exo:` followed by the action name you'd like to execute. For example: `exo:smtp-send`.

Open the Input/Output tab to specify input variables to your action. You can use any process variables and exo variables.

Example inputs:

* `to`: `joe@example.web`
* `from`: `Exo bot`
* `subject`: `Hello world!`
* `body`: `This is a demo`
* `dsn`: `smtp://user:pass@mail.example.web`

You can also specify an environment variable called `EXO__VARIABLES__SMTP_DSN` and specify `{{SMTP_DSN}}` as the `dsn` value. This way you don't need to hard-code the SMTP details in your BPMN process.

You can also specify `${someProcessVariable}` as a value to inject a process variable.

For actions that have output variables, you may wish to rename those before injecting them back into your process. For example, if a `get-user-data` action returns a `user` object, you may wish to rename this to `customer` (in order not to overwrite or handle multiple `user` variables in your process). This can be achieved by specifying an input variable `>user` with value `customer`.

## Logging

Exo and all it's commands support PSR-3 based logging. To log to a file, specify the following environment variable:

* `EXO_LOG=/var/log/exo.log`

Under the hood, Exo uses [monolog](https://github.com/Seldaek/monolog), meaning you can easily add [any of it's many handlers](https://seldaek.github.io/monolog/doc/02-handlers-formatters-processors.html) to log to email, slack, graylog, elastic, syslog, etc.

When running Exo through `./bin/console` you can pass `-v` to send log output to STDOUT (in addition to the optional log file).

## License

MIT. Please refer to the [license file](LICENSE) for details.

## Brought to you by the LinkORB Engineering team

<img src="http://www.linkorb.com/d/meta/tier1/images/linkorbengineering-logo.png" width="200px" /><br />
Check out our other projects at [linkorb.com/engineering](http://www.linkorb.com/engineering).

Btw, we're hiring!

## Git hooks

There are some git hooks under `.hooks` directory. Feel free to copy & adjust & use them
