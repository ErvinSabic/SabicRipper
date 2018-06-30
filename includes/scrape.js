var webPage = require('webpage');
var site = require('./page.json');
var page = webPage.create();

configuration = {
    debug: false, 
    interval: 5000,
    timeout: 30000,
    check: function() {
        return page.evaluate(function(){
            return !$('#SITE_CONTAINER').is(':empty')
        })
    },
    success: function() {
        console.log(page.content);
        phantom.exit();
    },
    error: function () {
        console.log("failed");
        phantom.exit();
    }
}

function waitFor ($config) {
    $config._start = $config._start || new Date();

    if ($config.timeout && new Date - $config._start > $config.timeout) {
        if ($config.error) $config.error();
        if ($config.debug) console.log('timed out ' + (new Date - $config._start) + 'ms');
        return;
    }

    if ($config.check()) {
        if ($config.debug) console.log('success ' + (new Date - $config._start) + 'ms');
        return $config.success();
    }

    setTimeout(waitFor, $config.interval || 0, $config);
}

waitFor(configuration);
page.open(site);