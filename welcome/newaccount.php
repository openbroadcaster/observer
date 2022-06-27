<div>

<h1>New Account</h1>

<form id="newaccount_form" onSubmit="return false;"> 
  <p id="newaccount_message">A random password will be emailed to you.</p>
  <input id="newaccount_name" type="text" aria-label="Name" placeholder="Name">
  <input id="newaccount_username" type="text" aria-label="Username" placeholder="Username">
  <input id="newaccount_email" type="text" aria-label="Email "placeholder="Email">
  <input id="newaccount_submit" type="button" value="Create New Account" onclick="OB.Welcome.newaccount();">
</form>

<div class="welcome_actions"><a href="javascript: OB.Welcome.show('login');">Return to Login</a></div>

</div>
