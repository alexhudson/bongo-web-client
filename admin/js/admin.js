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
						// load the data package from Bongo
						$this.model = ko.mapping.fromJS(result['data']);
						ko.applyBindings($this.model);
						$('#login-form').hide();
						$('#admin-tool').show();
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
		}
	};
	$this.backend = backend;
	return $this;
};

