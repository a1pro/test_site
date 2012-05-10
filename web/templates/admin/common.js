<SCRIPT language=JavaScript>
var DHTML = (document.getElementById || document.all || document.layers);

function showLayer(name,visibility)
{
    if (!DHTML) return;
    if (name)
    {
        var x = getObj(name);
        x.display = visibility ? '' : 'none';
    }
}

function getObj(name)
{
  if (document.getElementById)
  {
    return document.getElementById(name).style;
  }
  else if (document.all)
  {
    return document.all[name].style;
  }
  else if (document.layers)
  {
    return document.layers[name];
  }
  else return false;
}
</SCRIPT>
