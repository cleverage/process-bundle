#!/usr/bin/env bash

find . -type f -exec sed -i 's/CleverAge\\ProcessBundle\\Task\\File\\FileFetchTask/CleverAge\\FlysystemProcessBundle\\Task\\FileFetchTask/g' {} \;
