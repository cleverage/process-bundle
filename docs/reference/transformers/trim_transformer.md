TrimTransformer
=========================

Strip whitespace (or other characters) from the beginning and end of a string

This transformer uses the php internal function: https://www.php.net/manual/en/function.trim.php

Task reference
--------------

* **Service**: `CleverAge\ProcessBundle\Transformer\String\TrimTransformer`
* **Transformer code**: `trim`

Accepted inputs
---------------

Any value that can be cast to string and null.

Possible outputs
----------------

Depending on the input :
- `null` if the input is null
- `string` if the input is not null

Options
-------

| Code | Type | Required | Default               | Description                                                                                                                                                         |
| ---- | ---- | :------: |-----------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `charlist` | `string` | | ***" \t\n\r\0\x0B"*** | List of characters to trim |

