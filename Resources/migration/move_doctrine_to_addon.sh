#!/usr/bin/env bash

find . -type f -exec sed -i 's/CleverAge\\ProcessBundle\\Task\\Database/CleverAge\\ProcessBundle\\Addon\\Doctrine\\Task\\Database/g' {} \;
find . -type f -exec sed -i 's/CleverAge\\ProcessBundle\\Task\\Doctrine/CleverAge\\ProcessBundle\\Addon\\Doctrine\\Task\\EntityManager/g' {} \;
