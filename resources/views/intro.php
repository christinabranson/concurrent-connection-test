<html>

<head>
    <title>Concurrent Connection Test: Intro</title>
    <!-- Foundation CSS. It's gotta be at least a little pretty -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/foundation/6.2.3/foundation.min.css">
</head>

<body>
    <div class="row">
        <table>
            <thead>
                <tr>
                    <td>
                        Function
                    </td>
                    <td>
                        Description
                    </td>
                    <td>
                        Example Request
                    </td>
                    <td>
                        Test Results
                    </td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        baseline
                    </td>
                    <td>
                        Baseline function which simply performs an HTTP request (with Guzzle) and returns the response. <strong>Note: This method is expected to fail for more than 10 concurrent requests.</strong>
                    </td>
                    <td>
                        <a href="https://rocky-depths-72026.herokuapp.com/baseline">/baseline</a>
                    </td>
                    <td>
                        <a href="https://rocky-depths-72026.herokuapp.com/test/baseline">/test/baseline</a>
                    </td>
                </tr>
                <tr>
                    <td>
                        simpleCache
                    </td>
                    <td>
                        simpleCache stores a successful response in a cache. If a response fails, we pull a successful response from the cache. Cons of this method include displaying possibly outdated information to the user. A variation on this, if the situation calls for it, could be to check the cache before attempting the request.
                    </td>
                    <td>
                        <a href="https://rocky-depths-72026.herokuapp.com/simpleCache">/simpleCache</a>
                    </td>
                    <td>
                        <a href="https://rocky-depths-72026.herokuapp.com/test/simpleCache">/test/simpleCache</a>
                    </td>
                </tr>
                <tr>
                    <td>
                        simpleSleep
                    </td>
                    <td>
                        simpleSleep attempts to make a request, and if it fails, uses `usleep()` to delay the method before trying again. The cons of this method is that it actually increases the total number of requests made to the service.
                    </td>
                    <td>
                        <a href="https://rocky-depths-72026.herokuapp.com/simpleSleep">/simpleSleep</a>
                    </td>
                    <td>
                        <a href="https://rocky-depths-72026.herokuapp.com/test/simpleSleep">/test/simpleSleep</a>
                    </td>
                </tr>
                <tr>
                    <td>
                        logRequests
                    </td>
                    <td>
                         The goal of this method is to only make a request when you can reasonably
    believe that the response will be successful. We log the last request made,
    then calculate the time between now and that request. If the time difference meets the
    requirements, we can go ahead and make the request.
                    </td>
                    <td>
                        <a href="https://rocky-depths-72026.herokuapp.com/logRequests">/logRequests</a>
                    </td>
                    <td>
                        <a href="https://rocky-depths-72026.herokuapp.com/test/logRequests">/test/logRequests</a>
                    </td>
                </tr>

            </tbody>
        </table>

        <p><a href="https://github.com/christinabranson/concurrent-connection-test">Source Code</a></p>
        <p><a href="https://github.com/christinabranson/concurrent-connection-test/blob/master/README.md">Documentation</a></p>
    </div>
</body>

</html>