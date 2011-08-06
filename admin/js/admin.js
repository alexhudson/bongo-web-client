if (typeof(Bongo)=="undefined") Bongo = {};

Bongo.AdminTool = function(backend) {
	var $this = {
		init: function() {
			// set up login form.
			$('#login-form-button').click(function() {
				var data = $('#login-form').serializeArray();
				data.push({name: 'command', value: 'login'});
				$this.loadData(data, function(data) {
					var result = $.parseJSON(data.responseText);
					$('#login-form-message').text(result['message']);
				});
				return false;
			});
			$('#admin-logout').click(function() {
				$.cookie('bongo_admin_cookie', 'unset', { expires: -1, path: '/' });
				$('#login-form').show();
				$('#admin-tool').hide();
			});
			$('#admin-save').click(function () {
				$this.saveData();
				return false;
			});
			$('#admin-tabs').tabs();
			
			var existing_cookie = $.cookie('bongo_admin_cookie');
			if (existing_cookie) {
				$('#login-form').hide();
				// TODO: show some kind of holding thing?
				$this.loadData({ command: 'login', cookie: existing_cookie },
					function() {
						$('#login-form').show();
					});
			}
		},
		
		loadData: function(data, err) {
			$.post($this.backend, data, function (d, t, response) {
				var result = $.parseJSON(response.responseText);
				if (result['status'] ==  'ok') {
					$this.loadDataCallback(result);
				} else {
					$('#login-form-message').text(result['message']);
				}
			}, 'json').error(err);
		},
		
		loadDataCallback: function (result) {
			// load the data package from Bongo
			if (result['cookie'])
				$.cookie('bongo_admin_cookie', result['cookie'], { expires: 1, path: '/' });
			
			$this.original_data = $.extend(true, {}, result['data']);
			$this.model = ko.mapping.fromJS(result['data']);
			ko.applyBindings($this.model);
			$('#login-form').hide();
			$('#admin-tool').show();
		},
		
		saveData: function () {
			var current_data = ko.toJSON($this.model);
			
			var package = { command: 'savedata', data: current_data, cookie: $.cookie('bongo_admin_cookie') };
			$.post($this.backend, package, function (d, t, response) {
				var result = $.parseJSON(response.responseText);
				if (result['status'] ==  'ok') {
					alert('Done!');
				} else {
					// TODO show error
				}
			}, 'json').error(function () {
				alert('Something went wrong saving data');
			});
		}
	};
	$this.backend = backend;
	return $this;
};

