Exo: FaaS framework supporting externalized Actions and Triggers
================================================================

Inspired by the trends in Serverless / FaaS / Cloud Functions.

## Features:

* Provides a framework to build, call and test reusable, stateless, language agnostic functions (actions).
* A language agnostic (json) format to define `inputs`, `outputs` and `configs` that your actions need.
* "Exo Actions" are bundled into reusable "Exo Packages"
* Configure your Exo instance with a `exo.config.json` file, importing all the packages your app needs.
* Uses JSON Schema to validate every request, response, input, output and config.
* An HTTP end-point server to serve your functions.
* A Console tool to help build, test and debug your Exo functions.

## Examples:

The `example/` directory contains an example package with 2 functions, one implemented in PHP, and one generically executing an external CLI tool.

To test it out:

    cp .env.dist .env
    edit .env # setup your EXO_CONFIG path
    bin/exo action example/hello-php -i greeting=Hello -i name=Alice
    bin/exo run example/hello-php -i greeting=Hello -i name=Alice

This will call the `hello-php` Action, passing input arguments through stdin.

## Status

Exo is currently in an experimental phase, and some of the features are under construction.

## License

MIT. Please refer to the [license file](LICENSE) for details.

## Brought to you by the LinkORB Engineering team

<img src="http://www.linkorb.com/d/meta/tier1/images/linkorbengineering-logo.png" width="200px" /><br />
Check out our other projects at [linkorb.com/engineering](http://www.linkorb.com/engineering).

Btw, we're hiring!
