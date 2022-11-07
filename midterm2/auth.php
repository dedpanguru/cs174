// default landing page is registration
// include a button to change the html to a login form via js
// handle both registration and login via php
<html>
    <head><title>Midterm 2</title></head>
    <body>
        <h1><u>This is a Gurveer Singh Production</u></h1>
        <div id="registration">
            <pre><h2>Register</h2>
                <form action="auth.php" method="post" enctype="multipart/form-data">
                    Enter Username: <input type="text" name="username" required>
        
                    Enter Password: <input type="password" name="password" required>
                    
                    <input type="hidden" name="Register" value="true">
                    <input type="submit" value="Register">
                </form>
            </pre>
        </div>
        <div id="login">
            <pre><h2>Login</h2>
                <form action="auth.php" method="post" enctype="multipart/form-data">
                    Enter Username: <input type="text" name="username" required>
        
                    Enter Password: <input type="password" name="password" required>
                    
                    <input type="hidden" name="Login" value="true">
                    <input type="submit" value="Login">
                </form>
                <button type="click"></button>
            </pre>
        </div>
        
        <script>

        </script>
    </body>
</html>