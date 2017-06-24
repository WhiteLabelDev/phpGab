# phpGab

Gab.ai does not currently have a public API or [much](https://gab.ai/docs) documentation, so here are some basic, quick and dirty PHP functions to get you started talking to the Gab.ai API unofficially. Presently only posting a plain text Gab is supported, but this should be easy to extend to start doing other things.

This is wholly unofficial, likely to break, and hopefully a temporary solution until Gab release an official public API. Use at your own risk and be sure to follow the Gab terms of service: [https://gab.ai/about/tos](https://gab.ai/about/tos)

**Please be respectful and do not spam Gab with this script!**

You can follow us at [https://gab.ai/white_label_dev](https://gab.ai/white_label_dev)


## Features

1. Logs in to your Gab acocunt to retrieve tokens **automatically**
2. Locally caches bearer tokens for very fast future requests
3. Automatically detects expired tokens and changed credentials
4. Well documented, easy to extend to perform other Gab functionality
5. Works on PHP 5+, **no database required**
6. Licensed under MIT


## Usage

```
<?php

require_once 'phpgab.php';

//$debug = TRUE;

$GabResponse = Gab_Send ('your_gab_username', 'your_gab_password', 'This gab is from phpGab. Hello world!');
var_dump ($GabResponse);

?>

array(5) {
  ["id"]=>
  string(7) "9999999"
  ["published_at"]=>
  string(25) "2017-06-23T21:30:25+00:00"
  ["type"]=>
  string(4) "post"
  ["actuser"]=>
  array(8) {
    ["id"]=>
    int(195348)
    ["name"]=>
    string(12) "We make apps"
    ["username"]=>
    string(15) "white_label_dev"
    ["picture_url"]=>
    string(43) "https://files.gab.ai/user/594d067129f06.png"
    ["verified"]=>
    bool(false)
    ["is_donor"]=>
    bool(false)
    ["is_pro"]=>
    bool(false)
    ["is_private"]=>
    bool(false)
  }
  ["post"]=>
  array(22) {
    ["id"]=>
    int(9159923)
    ["created_at"]=>
    string(25) "2017-06-23T21:30:24+00:00"
    ["revised_at"]=>
    NULL
    ["edited"]=>
    bool(false)
    ["body"]=>
    string(48) "This gab is from phpGab. Hello world!"
    ["only_emoji"]=>
    bool(false)
    ["liked"]=>
    bool(false)
    ["disliked"]=>
    bool(false)
    ["bookmarked"]=>
    bool(false)
    ["repost"]=>
    bool(false)
    ["reported"]=>
    bool(false)
    ["score"]=>
    int(0)
    ["like_count"]=>
    int(0)
    ["dislike_count"]=>
    int(0)
    ["is_quote"]=>
    bool(false)
    ["is_reply"]=>
    bool(false)
    ["is_replies_disabled"]=>
    bool(false)
    ["embed"]=>
    array(2) {
      ["html"]=>
      NULL
      ["iframe"]=>
      NULL
    }
    ["category"]=>
    NULL
    ["category_details"]=>
    NULL
    ["language"]=>
    string(2) "en"
    ["user"]=>
    array(8) {
      ["id"]=>
      int(195348)
      ["name"]=>
      string(12) "We make apps"
      ["username"]=>
      string(15) "white_label_dev"
      ["picture_url"]=>
      string(43) "https://files.gab.ai/user/594d067129f06.png"
      ["verified"]=>
      bool(false)
      ["is_donor"]=>
      bool(false)
      ["is_pro"]=>
      bool(false)
      ["is_private"]=>
      bool(false)
    }
  }
}
```