<!--

function update_ticket() {
	//document.ticket.commit.value = "reload";
	cid=document.ticket.cid.options[document.ticket.cid.selectedIndex].value;
	location.href="index.php?whattodo=addjob&cid=" + cid;
	//document.reload();
}

function update_search() {
	table=document.search.table.options[document.search.table.selectedIndex].value;
	location.href="index.php?whattodo=search&table=" +table;
}

function update_contract() {
	cid=document.contract.cid.options[document.contract.cid.selectedIndex].value;
	location.href="index.php?whattodo=addcontract&cid=" + cid;
	//document.reload();
}

function update_customer() {
	//document.ticket.commit.value = "reload";
	document.contact.cid.options[selectedIndex].value=parent.ticket.cid.selectedIndex.value;
}

function update_items() {
	 var obj=document.ticket;
	 var cat=obj.category;
	 var item=obj.item;
	 var j=1;  
	for(var i=0;i<obj.item.options.length;i++){
     obj.item.remove(i);
  }
	item[0]=new Option("select item",0);
  for (var i=0;i<items_list.length;i++){
	 	if (items_list[i][0] == cat.options[cat.selectedIndex].value) {
	 		item[j] = new Option(items_list[i][2],items_list[i][1]);
			j++;
		}
	}
}

 function montre(id) {
	  if (document.getElementById) {
		  document.getElementById(id).style.display="inline";
		} else if (document.all) {
		  document.all[id].style.display="inline";
		} else if (document.layers) {
		  document.layers[id].display="inline";
		} } 

 function cache(id) {
	  if (document.getElementById) {
		  document.getElementById(id).style.display="none";
		} else if (document.all) {
		  document.all[id].style.display="none";
		} else if (document.layers) {
		  document.layers[id].display="none";
			} 
		} 
		
	function OpenNew(page) {
		OpenWin = this.open(page, "CtrlWindow", "toolbar=no,menubar=no,location=no,scrollbars=yes,resizable=yes");
	}

function fitWindowSize() {
	if (document.getElementById('tickets')) {
		window.resizeTo(500, 500);
		width = 500 - (document.body.clientWidth -  document.getElementById('tickets').width);
		height = 500 - (document.body.clientHeight -  document.getElementById('tickets').height);
		window.resizeTo(width, height);
		}
   }
	 
function changeparent() {
	opener.document.location.reload();
	self.close();
	//opener.contact.options[selectedIndex].value=parent.ticket.cid.selectedIndex.value;

   }


//-->
