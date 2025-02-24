document.addEventListener("DOMContentLoaded", function() {
    // Upload image button handler
    var mediaUploader;
    document.querySelector(".upload_image_button").addEventListener("click", function(e) {
        e.preventDefault();

        var inputField = document.getElementById("_easy_size_chart_image_field");

        if (!inputField) {
            console.error("Image path field not found!");
            return;
        }

        // If modal already exists, open it once more
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        // Media Library window
        mediaUploader = wp.media({
            title: "Choose an Image",
            button: { text: "Use this image" },
            multiple: false
        });

        // Handling image chooser
        mediaUploader.on("select", function() {
            var attachment = mediaUploader.state().get("selection").first().toJSON();
            inputField.value = attachment.url;              // Image path into form field
            inputField.dispatchEvent(new Event("change"));  // Forcing Woocommerce to refresh
        });

        // Open modal
        mediaUploader.open();
    });
});