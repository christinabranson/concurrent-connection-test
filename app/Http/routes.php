<?php

/*
    Introduction route
*/
$app->get('/', function () use ($app) {
    return view('intro');
});


/*
    Baseline
    Function does a simple HTTP request to the service and returns the response
    This method is expected to FAIL if more than 10 concurrent requests are made
*/
$app->get('/baseline', function () {
    Log::debug("baseline");
    return serviceCall();
});

/*
    Simple Caching
    If a request fails, pull a cached version of the response to be used
    temporarily until a new request can be made.
*/
$app->get('/simpleCache', function () {
    Log::debug("simpleCache");
    
    do {
        // get response
        $response = serviceCall();
        
        // if response fails, pull from the cache
        if (isFail($response)) {
            // check if there's a cached value that we can use
            if (Cache::has('response_value')){
                $response = Cache::get('response_value');
                
            // response failed and there is nothing in the cache
            } else {
                Log::error("No valid response in the cache.");
                // returns to beginning of the do and try again
            }
        // if response succeeds, store the response in the cache
        } else {
            Cache::put('response_value', $response, 1);
            Cache::put('response_time', microtime(true), 1);
        }
    // do it until the response is successful
    } while (isFail($response));
    
    // return a valid response
    return $response;
});

/*
    Simple Sleeping
    Uses usleep() to delay the request until a positive response can
    be made
*/
$app->get('/simpleSleep', function () {
    Log::debug("simpleSleep");
    
    // get response
    $response = serviceCall();
    
    // if request fails, delay then try again until we succeed
    if (isFail($response)) {
        Log::debug("response failed!");
        $i = 0; // counter to test # of attempts
        do {
            $SLEEP = rand(10,30);   // test to see if static sleep time or
                                    // staggered sleep time improves speed
            Log::debug("Retry attempt: " . $i . " with sleep time: " . $SLEEP);
            usleep($SLEEP);
            $response = serviceCall();
            $i++;
        } while(isFail($response));
    }
    
    return $response;
});

/*
    Log Requests
    The goal of this method is to only make a request when you can reasonably
    believe that the response will be successful. We log the last request made,
    then calculate the time between now and that request. If the time difference meets the
    requirements, we can go ahead and make the request.
    
    This should work for concurrency tests, but the method is probably more suited
    for an API that limits you by time (ie 1 request every 10 seconds). One simply
    sets the tolerance and sleep variables such that your requests don't
    violate that limit.
*/
$app->get('/logRequests', function () {
    Log::debug("logRequests");
    
    // these two items need to be tested and tweaked to meet the requirements
    $TOLERANCE = 0.0002; // 200 microseconds
    $SLEEP = 200;
    
    do {
        // get the time the last request was made
        if (Cache::has('last_request_time')){
            $last_request = Cache::get('last_request_time');
        }
        $now = microtime(true);
        
        // if there's a cached last request time
        if (isset($last_request)) {
            // check to see if its been an appropriate amount of time has passed
            // in order to make the request
            Log::debug("A cached last request time is set");
            Log::debug("Time difference: " . ($now - $last_request));
            if ($now - $last_request > $TOLERANCE) {
                // enough time has passed, let's make the request
                Log::debug("Enough time has passed, let's make the request");
                $response = serviceCall();
                Cache::put('last_request_time', microtime(true), 1); // Store the last request time!
                if (isFail($response)) {
                    Log::error("Request has failed");
                } else {
                    Log::debug("Successful request");
                }
            } else {
                // delay then try the request
                Log::debug("Not enough time has passed. Delay then make the call.");
                usleep($SLEEP);
                $response = serviceCall();
                Cache::put('last_request_time', microtime(true), 1); // Store the last request time!
            }
        // else there is no cached last request time
        // which means either that there's been no recent requests
        // or they've all appeared at the same time?
        } else {
            Log::debug("Enough time should have passed.. let's make the call");
            $response = serviceCall();
            Cache::put('last_request_time', microtime(true), 1); // Store the last request time!
        }
    } while(isFail($response));

    return $response;
});


/*
    Test route
    Takes in a specific method and runs a number of concurrent HTTP requests
    and returns a simple report on the responses.
*/
$app->get('/test/{method}', function ($method) {
    Log::debug("test: " . $method);
    
    // Choose which method/route to test
    if (
        $method == "baseline" ||
        $method == "simpleCache" ||
        $method == "simpleSleep" ||
        $method == "logRequests"
        )
        $url = URL::to('/'.$method);
    // if invalid route, use the service itself
    else 
        $url = "http://service.sutter.arkitech.net/";

    // number of attempts to make (should be > 11)
    $attempts = 15;
    
    // display the test parameters
    echo "URL: " . $url . "<br/>";
    echo "Number of Requests: " . $attempts . "<br/><br/>";

    // make new guzzle client
    $client = new GuzzleHttp\Client();
    
    // make all the async requests
    $promises = array();
    for ($i = 0; $i < $attempts; $i++) {
        $promises[] = $client->requestAsync('GET', $url);
    }
    
    // process the responses
    // return response in red if it fails
    GuzzleHttp\Promise\all($promises)->then(function (array $responses) {
        $i = 0;
        foreach ($responses as $response) {
            echo "Request " . $i  . ": ";
            $res = $response->getBody();
            // if fails, highlight in red
            if (isFail($res)) {
                echo "<span style='color: red;'>" . $res . "</span><br />";
            } else {
                echo $res . "<br />";
            }
            Log::debug($response->getBody());
            $i++;
        }
    })->wait();
});


/* Helper functions */
// Make HTTP request to external service and return response
function serviceCall() {
    $url = "http://service.sutter.arkitech.net/";
    $client = new GuzzleHttp\Client();

    try {
        $response = $client->get($url)->getBody()->getContents();
    } catch (ClientException $e) {
        Log::error(Psr7\str($e->getRequest()));
        Log::error(Psr7\str($e->getResponse()));
    }
    return $response;
}

// Check to see if response is successful or fails based on
// presence of "success":0 
// returns true if it is a failed response
function isFail($response) {
    if (strpos($response, '"success":0'))
        return true;
    else
        return false;
}