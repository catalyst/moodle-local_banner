define(['local_banner/cropper'], function (module) {
    return {
        cropper: function($params) {
            var image = document.getElementById('bannerimage');
            var cropper = new Cropper(image, {
                dragMode: 'move',
                aspectRatio: 16 / 9,
                autoCropArea: 0.65,
            });

            return window.Cropper;
        }
    }
});