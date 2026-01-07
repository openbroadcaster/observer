<div>

  <h1>Forgot Password</h1>

  <form id="forgotpass_form" onSubmit="return false;">
    <p id="forgotpass_message">Your username and a new password will be emailed to you.</p>
    <input aria-label="Email" placeholder="Email" id="forgotpass_email" type="text" size="25" name="email">
    <input id="forgotpass_submit" type="button" name="submit" value="Submit" onclick="OB.Welcome.forgotpass();">
  </form>
  
  <div class="welcome_actions"><a href="javascript: OB.Welcome.show('login');">Return to Login</a></div>

</div>

