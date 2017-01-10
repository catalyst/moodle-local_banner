define(['local_banner/cropper'], function (module) {
    return {
        cropper: function($params) {
            var image = document.getElementById('bannerimage');
            var tx = document.getElementById('tx');
            var ty = document.getElementById('ty');

            var cx = document.getElementsByName('cropx')[0];
            var cy = document.getElementsByName('cropy')[0];

            var cropper = new Cropper(image, {
                dragMode: 'move',
                viewMode: 1,
                aspectRatio: 3 / 1,
                autoCropArea: 0.65,

                // Updating the values each time the crop changes.
                crop: function(e) {
                    var data = cropper.getData(true);

                    tx.value = data.x;
                    ty.value = data.y;
                },

                // Only updating the values when ending the crop.
                cropend: function(e) {
                    var data = cropper.getData(true);

                    cx.value = data.x;
                    cy.value = data.y;
                }
            });

            return cropper;
        }
    };
});