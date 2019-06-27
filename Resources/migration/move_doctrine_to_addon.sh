#!/usr/bin/env bash

find . -type f -exec sed -i 's/CleverAge\\ProcessBundle\\Task\\Database/CleverAge\\DoctrineProcessBundle\\Task\\Database/g' {} \;
find . -type f -exec sed -i 's/CleverAge\\ProcessBundle\\Task\\Doctrine/CleverAge\\DoctrineProcessBundle\\Task\\EntityManager/g' {} \;
