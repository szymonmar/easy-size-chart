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

    document.querySelector(".add_row_button").addEventListener("click", function(e) {
        e.preventDefault();
        updateTableSize(1, 0);
    });

    document.querySelector(".add_column_button").addEventListener("click", function(e) {
        e.preventDefault();
        updateTableSize(0, 1);
    });

    document.querySelector(".delete_row_button").addEventListener("click", function(e) {
        e.preventDefault();
        updateTableSize(-1, 0);
    });

    document.querySelector(".delete_column_button").addEventListener("click", function(e) {
        e.preventDefault();
        updateTableSize(0, -1);
    });


    function updateTableSize(rowIncrement, colIncrement) {
        let rowCountField = document.getElementById("_easy_size_chart_row_count_field");
        let colCountField = document.getElementById("_easy_size_chart_column_count_field");
        let tableContainer = document.getElementById("_easy_size_chart_table");

        if (!rowCountField || !colCountField || !tableContainer) {
            console.error("Nie znaleziono pól formularza!");
            return;
        }

        if(rowIncrement > 0 && rowCountField.value >= 10) {
            return;
        }
        if(colIncrement > 0 && colCountField.value >= 10) {
            return;
        }
        if(rowIncrement < 0 && rowCountField.value <= 1) {
            return;
        }
        if(colIncrement < 0 && colCountField.value <= 1) {
            return;
        }
            
        let newRowCount = parseInt(rowCountField.value) + rowIncrement;
        let newColCount = parseInt(colCountField.value) + colIncrement;

        rowCountField.value = parseInt(rowCountField.value) + rowIncrement;
        colCountField.value = parseInt(colCountField.value) + colIncrement;

        colCountField.dispatchEvent(new Event("change"));
        rowCountField.dispatchEvent(new Event("change"));

        let formData = new FormData();
        formData.append("action", "modify_global_table_size");
        formData.append("post_id", document.getElementById("post_ID").value);
        formData.append("row_count", newRowCount);
        formData.append("column_count", newColCount);

        fetch(ajaxurl, {
            method: "POST",
            body: formData,
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    tableContainer.innerHTML = data.data.table_html;
                } else {
                    console.error("AJAX error: ", data.data.message);
                }
            })
            .catch(error => console.error("AJAX connection error: ", error));
    }
});