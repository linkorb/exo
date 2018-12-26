Exo: FaaS framework supporting externalized Actions and Triggers
================================================================

Inspired by the trends in Serverless / FaaS / Cloud Functions.

## Features:

* Provides a framework to build, call and test reusable stateless functions (actions).
* A language agnostic (json) format to define `inputs`, `outputs` and `configs` that your actions need.
* A service.json format to list the Exo Actions you'd like to expose.
* Uses JSON Schema to validate all input, output and configs.
* Invokers for your Exo Action, so you can easily call/host them locally or remotely.
* Supports functions implemented in PHP or any other language, including executing external commands.
* An HTTP end-point server to serve your functions.
* A Console tool to help build, test and debug your Exo functions.

## Examples:

The `example/` directory contains an example service with 2 functions, one implemented in PHP, and one generically executing an external CLI tool.

To test it out:

    cd example/
    ../bin/exo run example/hello-php/exo.action.json < example/input.example.json

This will call the `hello-php` Action, passing input arguments through stdin.

## Status

Exo is currently in an experimental phase, and some of the features are under construction.

## License

MIT. Please refer to the [license file](LICENSE) for details.

## Brought to you by the LinkORB Engineering team

<img src="http://www.linkorb.com/d/meta/tier1/images/linkorbengineering-logo.png" width="200px" /><br />
Check out our other projects at [linkorb.com/engineering](http://www.linkorb.com/engineering).

Btw, we're hiring!
