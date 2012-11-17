var tracking = {
	xhr : new XMLHttpRequest(),
	id  : null,
	send : function () {
		if (null !== tracking.id)
			tracking.xhr.send("id=" + tracking.id);
	}
};

tracking.xhr.open("POST", "/ajax/tracking", true);
window.onbeforeunload = tracking.send;
