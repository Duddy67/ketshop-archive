
//A simple check function for Joomla forms.
function checkForm(form)
{
  //List all of the tags whithin the form.
  for(var i = 0; i < form.length; i++)
  {
    var tagName = form[i].tagName.toLowerCase();

    //Get only the tags involved, (input, select etc...).
    if(tagName != 'label' && tagName != 'fieldset')
    {
      //Check if the tag class name contains "required". 
      var regex = /required/;
      if(regex.test(form[i].className))
      {
	//Check for empty value or white space(s).
	regex = /^\s*$/;
	if(regex.test(form[i].value))
	{
	  //Get the id of the corresponding label tag.
	  var labelId = form[i].id + '-lbl';
	  //Color the unset field and its label in red.
	  document.getElementById(labelId).style.color='red';
	  form[i].style.borderColor = 'red';
	  //Set the focus on the unset field.
	  form[i].focus();

	  return false;
	}
      }
    }
  }

  return true;
}


//Create a div and put a given text in it.
function getMessagePanel(panelId, message)
{
  var messagePanel = document.createElement('div');
  messagePanel.setAttribute('id', panelId);
  var text = document.createTextNode(message);
  messagePanel.appendChild(text); //Insert the text whithin div tag.

  return messagePanel;
}
