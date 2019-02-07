#!/usr/bin/env bash

find . -type f -exec sed -i 's/CleverAge\\ProcessBundle\\Task\\File\\FileFetchTask/CleverAge\\ProcessBundle\\Addon\\Flysystem\\Task\\FileFetchTask/g' {} \;
