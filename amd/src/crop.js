define(['local_banner/cropper'], function (module) {
    return {
        cropper: function(params) {
            var image = document.getElementById('bannerimage');
            var cropper = new Cropper(image, {
                viewMode: 1,
                aspectRatio: 3 / 1,

                ready: function () {
                    var data = {
                        x: params.x,
                        y: params.y,
                        scaleX: params.scaleX,
                        scaleY: params.scaleY,
                        height: params.height,
                        width: params.width,
                        rotate: params.rotate
                    };

                    this.cropper.setData(data);
                },

                // Updating the values each time the crop changes.
                crop: function(e) {
                    var cx = document.getElementsByName('cropx')[0];
                    var cy = document.getElementsByName('cropy')[0];
                    var sx = document.getElementsByName('scalex')[0];
                    var sy = document.getElementsByName('scaley')[0];
                    var h = document.getElementsByName('height')[0];
                    var w = document.getElementsByName('width')[0];
                    var r = document.getElementsByName('rotate')[0];
                    var data = this.cropper.getData(true);

                    cx.value = data.x;
                    cy.value = data.y;
                    sx.value = data.scaleY;
                    sy.value = data.scaleX;
                    h.value = data.height;
                    w.value = data.width;
                    r.value = data.rotate;
                }
            });

            return cropper;
        }
    };
});