jQuery(document).ready(function($) {
    var app = angular.module('formApp', []);

    app.controller('formController', ['$scope', '$http', function ($scope, $http) {
        // Initialize with one empty client object, the first one will be the holder
        $scope.clients = [{holder: true}];

        // Store the form data in JSON format
        $scope.jsonData = null;

        // Function to add another client form
        $scope.addClient = function () {
            $scope.clients.push({holder: false}); // Add a new empty client object with holder false
        };

        // Function to remove a client form by index
        $scope.removeClient = function (index) {
            if ($scope.clients.length > 1) {
                $scope.clients.splice(index, 1); // Remove client at the given index
                // Automatically set the first form as the holder if no holder is selected
                if (!$scope.clients.some(client => client.holder)) {
                    $scope.clients[0].holder = true;
                }
            } else {
                toastr.warning('You must have at least one client form.');
            }
        };

        // Function to ensure only one form can be the "Form Holder"
        $scope.updateFormHolder = function (index) {
            // If the checkbox is selected, uncheck all others
            if ($scope.clients[index].holder) {
                $scope.clients.forEach(function (client, i) {
                    if (i !== index) {
                        client.holder = false;
                    }
                });
            }
        };

        // Function to handle form submission
        $scope.submitForm = function () {
            // Collect form data in jsonData
            $scope.jsonData = angular.copy($scope.clients); // Make a copy of the clients array
            
            console.log('Submitting form data:', $scope.jsonData);

            // AJAX submission
            $http({
                method: 'POST',
                url: form_handler_data.ajax_url,
                data: $.param({
                    action: 'form_handler_submit',
                    nonce: form_handler_data.nonce,
                    clients: JSON.stringify($scope.jsonData)
                }),
                headers: {'Content-Type': 'application/x-www-form-urlencoded'}
            }).then(function successCallback(response) {
                console.log('Server response:', response);
                if (response.data.success) {
                    console.log('Form Submitted:', response.data.data);
                    toastr.success(response.data.data.message);
                    // Clear the form after successful submission
                    $scope.clients = [{holder: true}];
                    $scope.jsonData = null;
                } else {
                    console.error('Submission error:', response.data.data);
                    toastr.error(response.data.data);
                }
            }, function errorCallback(response) {
                console.error('Submission error:', response);
                toastr.error('Error submitting form. Please try again.');
            });
        };
    }]);

    // Manual bootstrap of the AngularJS app
    angular.bootstrap(document.querySelector('[ng-app="formApp"]'), ['formApp']);
});
