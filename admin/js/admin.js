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
					console.log(response.responseText);
					if (result['status'] ==  'ok') {
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
		}
	};
	$this.backend = backend;
	return $this;
};

