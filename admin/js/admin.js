if (typeof(Bongo)=="undefined") Bongo = {};

Bongo.AdminTool = function(backend) {
	var $this = {
		init: function() {
			// set up login form.
			$('#login-form-button').click(function() {
				var data = $('#login-form').serializeArray();
				data.push({name: 'command', value: 'login'});
				$.post($this.backend, data, function (d, t, response) {
					var result = $.parseJSON(response.responseText);
					if (result['status'] ==  'ok') {
						$this.loadData(result);
					} else {
						$('#login-form-message').text(result['message']);
					}
				}, 'json').error(function(data) {
					var result = $.parseJSON(data.responseText);
					$('#login-form-message').text(result['message']);
				});
				return false;
			});
			$('#admin-tabs').tabs();
			
			var existing_cookie = $.cookie('bongo_admin_cookie');
			if (existing_cookie) {
				$('#login-form').hide();
				// TODO: show some kind of holding thing?
				$.post($this.backend, { command: 'login', cookie: existing_cookie }, function (d, t, response) {
					var result = $.parseJSON(response.responseText);
					if (result['status'] ==  'ok') {
						$this.loadData(result);
					} else {
						$('#login-form').show();
					}
				}, 'json').error(function() {
					$('#login-form').show();
				});
			}
		},
		
		loadData: function (result) {
			// load the data package from Bongo
			if (result['cookie'])
				$.cookie('bongo_admin_cookie', result['cookie'], { expires: 1, path: '/' });
			$this.model = ko.mapping.fromJS(result['data']);
			ko.applyBindings($this.model);
			$('#login-form').hide();
			$('#admin-tool').show();
		}
	};
	$this.backend = backend;
	return $this;
};

