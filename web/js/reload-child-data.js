jQuery(document).ready(function($) {
    setTimeout("updateData()", 10000);
});
 
function updateData()
{
    // // want next update exactly 10s after last, so don't embed the recall inside the getJSON block...
    if (!(parseInt($("tbody#data tr td.queued").last().text())==0 && parseInt($("tbody#data tr td.inprogress").last().text())==0))
    {
        setTimeout("updateData()",10000);
        $.getJSON('/show-child-progress.php?parent='+getParameterByName('parent')+"&ajax=yes", function(data) {
            var time = parseInt($("tbody#data tr td.time").last().text())+10;
            $("tbody#data").append("<tr><td class='time'>"+time+"</td><td class='queued'>"+data.queued+"</td><td class='inprogress'>"+data.inprogress+"</td><td class='completed'>"+data.completed+"</td></tr>");
            updateGraph();
        });
    }
}

function updateGraph()
{
    var queued = '';
    var inprogress = '';
    var completed = '';
    $("tbody tr").each(function() {
        if (inprogress=='')
        {
           inprogress= $(this).find('td.inprogress').text();
           
        }
        else
        {
            inprogress= inprogress + "," + $(this).find('td.inprogress').text();            
        }
        if (queued=='')
        {
           queued= $(this).find('td.queued').text();
           
        }
        else
        {
            queued= queued + "," + $(this).find('td.queued').text();            
        }
        if (completed=='')
        {
           completed= $(this).find('td.completed').text();
           
        }
        else
        {
            completed= completed + "," + $(this).find('td.completed').text();            
        }
    });
    $("#chart").html('<img src="http://chart.apis.google.com/chart?chs=440x220&cht=lxy&chco=3072F3,FF0000,FF9900&chds=20,108.333,1,10000,1.667,100,1,20,0,100,1,10000&chd=t:-1|'+queued+'|-1|'+inprogress+'|-1|'+completed+'&chdl=Queued|In+Progress|Completed&chdlp=b&chls=2,4,1|1|1&chma=5,5,5,25"/>')
}
 
function getParameterByName(name)
{
   name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
   var regexS = "[\\?&]"+name+"=([^&#]*)";
   var regex = new RegExp( regexS );
   var results = regex.exec( window.location.href );
   if( results == null )
     return "";
   else
     return decodeURIComponent(results[1].replace(/\+/g, " "));
}
