### Hexlet tests and linter status:
[![Actions Status](https://github.com/bugrov/php-phpunit-testing-project-75/workflows/hexlet-check/badge.svg)](https://github.com/bugrov/php-phpunit-testing-project-75/actions)

# Page-loader
A page-loader is a cli tool that downloads the requested web page with local resources

This is a third level project. created as part of the [Hexlet](https://ru.hexlet.io/) training course [https://ru.hexlet.io/programs/php-phpunit-testing/projects/75](https://ru.hexlet.io/programs/php-phpunit-testing/projects/75)

## Features

- Enter the web address and the page-loader will download it.
- The tool downloads all the resources listed on the page and changes the page so that it starts referencing local versions.
- Usage as CLI util
- Supports logging

## Installation

```bash
git clone https://github.com/bugrov/php-phpunit-testing-project-75.git

cd php-phpunit-testing-project-75
```

## Usage

```bash
Downloads page from URL and save it locally 

Usage:                                                                                                   
    page-loader (-h|--help)                                                                              
    page-loader [(-o|--output) <dir>] <url>                                                                   
    page-loader (-v|--version)                                                                           
                                                                                                         
Options:                                                                                                 
  -h --help            display help for command                                                          
  -v --version         output the version number                                                         
  -o --output <dir>    output dir [default: current directory]
```

## Example

[![asciicast](https://asciinema.org/a/xOsmDdjepD2h7E01Nit54Kh9v.svg)](https://asciinema.org/a/xOsmDdjepD2h7E01Nit54Kh9v)
