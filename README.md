# Handling Concurrent User Restrictions With an External Service

## Requirements

## Assumptions

* We want to minimize user wait time as best as possible.
* Real-time response > cached response > timeout/long delays/never-ending queues/poor UX

## Methods

For this exercise, I've implemented a few different solutions each as its own method. Each method
is linked to a route for testing, and each one attempts to be its own solution (except baseline, which
is meant to fail for multiple concurrent users).

| Function | Description | Example Request | Test Results |
| ----- | ----- | ----- | ----- |
| baseline | Baseline function which simply performs an HTTP request (with Guzzle) and returns the response. **Note: This method is expected to fail for more than 10 concurrent requests.** | [/baseline](https://rocky-depths-72026.herokuapp.com//baseline){:target="_blank"} | [/test/baseline](https://rocky-depths-72026.herokuapp.com/test/baseline){:target="_blank"} |
| simpleCache | simpleCache stores a successful response in a cache. If a response fails, we pull a successful response from the cache. Cons of this method include displaying possibly outdated information to the user. A variation on this, if the situation calls for it, could be to check the cache before attempting the request. | [/simpleCache](https://rocky-depths-72026.herokuapp.com/simpleCache){:target="_blank"} | [/test/simpleCache](https://rocky-depths-72026.herokuapp.com/test/simpleCache){:target="_blank"} |
| simpleSleep | simpleSleep attempts to make a request, and if it fails, uses `usleep()` to delay the method before trying again. The cons of this method is that it actually increases the total number of requests made to the service.  | [/simpleSleep](https://rocky-depths-72026.herokuapp.com/simpleSleep){:target="_blank"} | [/test/simpleSleep](https://rocky-depths-72026.herokuapp.com/test/simpleSleep){:target="_blank"} |
| logRequests | The goal of this method is to only make a request when you can reasonably believe that the response will be successful. We log the last request made, then calculate the time between now and that request. If the time difference meets the requirements, we can go ahead and make the request. | [/logRequests](https://rocky-depths-72026.herokuapp.com/logRequests){:target="_blank"} | [/test/logRequests](https://rocky-depths-72026.herokuapp.com/test/LogRequests){:target="_blank"} |


## Notes

#### On using Lumen/Laravel...

I chose to use Lumen for this exercise because I knew I wanted to test out multiple options. I wanted a basic framework so 
that I'd have access to an ORM, caching functionality, and an easy to way to make users/authorization if testing needed.

#### On caching...

I've chosen to use a file system cache for simplicity's sake when deploying to Heroku. Memcached or Redis would 
probably be faster and a better option in a real world application.

If I chose to use a database for the cache for this exercise, per Laravel 

In general, the ability to use caching in a real world application depends highly on the functionality 
of the service being called. For example, if you're attempting to post a payment to a payment service, it's
obviously not acceptable to simply ignore a failed response and return a cached value to the user. However, if 
you're returning (for example) a list of friends online, it might be acceptable to have delayed information (possibly 
mitigated by an AJAX request on the page to update realtime information, a notice to the user that there might be a 
delay in processing information, or a notification that the site is under heavy activity).

#### On real world implementation...

For this exercise,...

For a real application, the external service would probably have some required parameters,
likely related to the user performing the request. In most cases, I'd expect that the cached requests 
made by User1 would be inappropriate to display to User2. 

Another possible solution, which I didn't implement here, would be to let a background script perform
the external service requests during periods of high traffic volume. One would store the request attempts 
in a DB like so:

| Column | Type | Description |
| ----- | ----- | ----- |
| request_id | increments | ID of the request |
| user_id | integer | User making the request |
| params | string | JSON of the request `{key0: val0; key1: val1; key2: val2}` |
| time_request | time | Time the user made request |
| time_fulfilled | time | Time the user's request was successfully fulfilled |
| response | string | Successful JSON response |

And let another script execute these in the background whenever the external service limits were
not being maxed by users. The web application would then check this table before making the API called
if the last request had occurred within some acceptable time period.

This exercise also has all the functionality in one file. For a real application, the external
service would probably have its own class where it would perform the request and format the response
to whatever the application needs. Depending on the real-world requirements, the
chosen function would probably be implemented within that class.