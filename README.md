Opauth-LINE
=============
[Opauth][1] strategy for LINE.

Implemented based on https://developers.line.me/web-api/integrating-web-login-v2 using OAuth 2.0.

Opauth is a multi-provider authentication framework for PHP.

Getting started
----------------
1. Install Opauth-LINE:
   ```bash
   cd path_to_opauth/Strategy
   git clone git://github.com/YuzuruS/opauth-line.git LINE
   ```

   or

   ```bash
   composer require yuzuru-s/opauth-line
   ```

2. Create a LINE Login application at https://business.line.me/
   - Callback URL: enter `http://path_to_opauth/line/oauth2callback`

   
3. Configure Opauth-LINE strategy.

4. Direct user to `http://path_to_opauth/line` to authenticate


Strategy configuration
----------------------

Required parameters:

```php
<?php
'LINE' => array(
	'channel_id' => 'YOUR CHANNEL ID',
	'channel_secret' => 'YOUR CHANNEL SECRET'
)
```


License
---------
Opauth-LINE is MIT Licensed  
Copyright © 2017 Yuzuru Suzuki (http://yuzurus.hatenablog.jp/)

[1]: https://github.com/uzyn/opauth
