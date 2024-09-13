/*
    Copyright 2012-2024 OpenBroadcaster, Inc.

    This file is part of OpenBroadcaster Server.

    OpenBroadcaster Server is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    OpenBroadcaster Server is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with OpenBroadcaster Server.  If not, see <http://www.gnu.org/licenses/>.
*/

// get the media format settings.
OB.Media.settings = function () {
    OB.API.post("media", "formats_get", {}, function (data) {
        OB.UI.replaceMain("media/settings.html");

        formats = data.data;

        var video = formats.video_formats;
        var image = formats.image_formats;
        var audio = formats.audio_formats;
        var document = formats.document_formats;

        for (var i in video) {
            $(".video_formats[value=" + video[i] + "]").attr("checked", true);
        }

        for (var i in audio) {
            $(".audio_formats[value=" + audio[i] + "]").attr("checked", true);
        }

        for (var i in image) {
            $(".image_formats[value=" + image[i] + "]").attr("checked", true);
        }

        for (var i in document) {
            $(".document_formats[value=" + document[i] + "]").attr("checked", true);
        }

        OB.Media.categoriesGet();
        OB.Media.metadataGet();
        OB.Media.fieldsGet();
        OB.Dayparting.load();
        OB.Media.recordingDefaultsGet();
    });
};

OB.Media.genresGet = function () {
    OB.API.post("metadata", "genre_list", {}, function (response) {
        if (!response.status) return;

        genres = response.data;

        if (genres.length == 0) {
            //T No genres found.
            $("#media_genres").html('<td colspan="3" >' + OB.t("No genres found.") + "</td>");
            return;
        }

        $.each(genres, function (index, genre) {
            var $tr = $(
                '<tr class="hidden" id="media_genre_' +
                    genre.id +
                    '" data-category_id=' +
                    genre.media_category_id +
                    "></tr>",
            );
            $tr.append("<td>" + htmlspecialchars(genre.name) + "</td>");
            $tr.append("<td>" + htmlspecialchars(genre.description) + "</td>");
            //T Edit
            $tr.append('<td><a href="javascript:OB.Media.genreAddeditWindow(' + genre.id + ');" data-t>Edit</a></td>');

            // place after category name, or last item of that category id.
            if ($("#media_categories tr[data-category_id=" + genre.media_category_id + "]").length)
                $("#media_categories tr[data-category_id=" + genre.media_category_id + "]")
                    .last()
                    .after($tr.outerHTML());
            else $("#media_category_" + genre.media_category_id).after($tr.outerHTML());

            $("#media_genre_" + genre.id).data("details", genre);
        });
    });
};

OB.Media.genresToggle = function (catid) {
    if (!$("#media_category_" + catid).length) return;

    var expanded = $("#media_category_" + catid).attr("data-expanded");

    if (expanded == "true") {
        $("#media_categories tr[data-category_id=" + catid + "]").hide();
        $("#media_category_" + catid).attr("data-expanded", "false");
    } else {
        $("#media_categories tr[data-category_id=" + catid + "]").show();
        $("#media_category_" + catid).attr("data-expanded", "true");
    }
};

OB.Media.genreAddeditWindow = function (id) {
    if (id) {
        var genre = $("#media_genre_" + id).data("details");
        if (!genre) return;
    }

    OB.UI.openModalWindow("media/genre_addedit.html");

    var categories = $("#media_categories").data("categories");
    $.each(categories, function (index, category) {
        $("#genre_categories").append(
            '<option value="' + category.id + '">' + htmlspecialchars(category.name) + "</option>",
        );
    });

    if (id) {
        $("#genre_addedit_heading").text(OB.t("Edit Genre"));
        $("#genre_name").val(genre.name);
        $("#genre_description").val(genre.description);
        $("#genre_categories").val(genre.media_category_id);
        $("#genre_addedit_id").val(id);
        $("#genre_id").text(id);
        $("#genre_default").val(genre.is_default);
    } else {
        $("#genre_default").val(0);
        //T New Genre
        $("#genre_addedit_heading").text(OB.t("New Genre"));
        $(".edit_only").hide();
    }
    /*  else $('legend').text('New Heading');*/
};

OB.Media.genreSave = function () {
    var postfields = new Object();
    postfields.id = $("#genre_addedit_id").val();
    postfields.name = $("#genre_name").val();
    postfields.description = $("#genre_description").val();
    postfields.media_category_id = $("#genre_categories").val();
    postfields.default = $("#genre_default").val();

    OB.API.post("metadata", "genre_save", postfields, function (data) {
        if (data.status == true) {
            OB.UI.closeModalWindow();
            OB.Media.categoriesGet();

            //T Genres Updated.
            $("#media_categories_message").obWidget("success", "Genres Updated.");
            OB.Settings.getSettings(); // refresh the client-side media settings data
        } else {
            $("#genre_addedit_message").obWidget("error", data.msg);
        }
    });
};

OB.Media.genreDelete = function (confirm) {
    if (confirm) {
        OB.API.post("metadata", "genre_delete", { id: $("#genre_addedit_id").val() }, function (data) {
            if (data.status == false) {
                $("#media_categories_message").obWidget("error", data.msg);
            } else {
                OB.UI.closeModalWindow();
                OB.Media.categoriesGet();

                //T Genre Deleted.
                $("#media_categories_message").obWidget("success", "Genre Deleted.");
                OB.Settings.getSettings(); // refresh the client-side media settings data
            }
        });
    } else {
        //T Are you sure you want to delete the genre?
        //T Yes, Delete
        //T No, Cancel
        OB.UI.confirm(
            "Are you sure you want to delete the genre?",
            function () {
                OB.Media.genreDelete(true);
            },
            "Yes, Delete",
            "No, Cancel",
            "delete",
        );
    }
};

OB.Media.categoriesGet = function () {
    $("#media_categories").html("");

    OB.API.post("metadata", "category_list", {}, function (response) {
        if (!response.status) return;

        categories = response.data;

        $("#media_categories").data("categories", categories);

        if (categories.length == 0) {
            //T No categories found.
            $("#media_categories").html('<td colspan="3" data-t>No categories found.</td>');
            return;
        }

        //T Edit
        var edit_button_text = OB.t("Edit");

        $.each(categories, function (index, category) {
            var $tr = $('<tr data-expanded="false" id="media_category_' + category.id + '"></tr>');
            $tr.append(
                '<td colspan="2"><a href="javascript: OB.Media.genresToggle(' +
                    category.id +
                    ');">' +
                    htmlspecialchars(category.name) +
                    "</a></td>",
            );
            $tr.append(
                '<td><a href="javascript:OB.Media.categoryAddeditWindow(' +
                    category.id +
                    ');" >' +
                    edit_button_text +
                    "</a></td>",
            );

            $("#media_categories").append($tr.outerHTML());

            $("#media_category_" + category.id).data("details", category);
        });

        OB.Media.genresGet();
    });
};

OB.Media.categoryAddeditWindow = function (id) {
    if (id) {
        var category = $("#media_category_" + id).data("details");
        if (!category) return;
    }

    OB.UI.openModalWindow("media/category_addedit.html");

    if (id) {
        //T Edit Category
        $("#category_addedit_heading").text(OB.t("Edit Category"));
        $("#category_name_input").val(category.name);
        $("#category_addedit_id").val(id);
        $("#category_default").val(category.is_default);
        $("#category_id").text(id);
    } else {
        //T New Category
        $("#category_addedit_heading").text(OB.t("New Category"));
        $("#layout_modal_window .edit_only").hide();
        $("#category_default").val(0);
    }
};

OB.Media.categorySave = function () {
    var postfields = new Object();
    postfields.id = $("#category_addedit_id").val();
    postfields.name = $("#category_name_input").val();
    postfields.default = $("#category_default").val();

    OB.API.post("metadata", "category_save", postfields, function (response) {
        if (response.status == false) {
            $("#category_addedit_message").obWidget("error", response.msg);
        } else {
            OB.UI.closeModalWindow();
            OB.Media.categoriesGet();

            //T Category saved.
            $("#media_categories_message").obWidget("success", "Category saved.");
            OB.Settings.getSettings(); // refresh the client-side media settings data
        }
    });
};

OB.Media.categoryDelete = function (confirm) {
    var cat_id = $("#category_addedit_id").val();
    var cat_name = $("#media_categories #media_category_" + cat_id + " td a")
        .first()
        .text();

    if (!confirm) {
        //T Are you sure you want to delete this category (%1)?
        //T Yes, Delete
        //T No, Cancel
        OB.UI.confirm(
            ["Are you sure you want to delete this category (%1)?", cat_name],
            function () {
                OB.Media.categoryDelete(true);
            },
            "Yes, Delete",
            "No, Cancel",
            "delete",
        );
    } else {
        OB.API.post("metadata", "category_delete", { id: cat_id }, function (response) {
            if (response.status == false) {
                $("#category_addedit_message").obWidget("error", response.msg);
            } else {
                OB.UI.closeModalWindow();
                OB.Media.categoriesGet();

                //T Category deleted.
                $("#media_categories_message").obWidget("success", "Category deleted.");
                OB.Settings.getSettings(); // refresh the client-side media settings data
            }
        });
    }
};

// save new media format settings.
OB.Media.formatsSave = function () {
    var audio_formats = Array.from(document.querySelectorAll(".audio_formats"))
        .filter((elem) => elem.checked)
        .map((elem) => elem.value);
    var image_formats = Array.from(document.querySelectorAll(".image_formats"))
        .filter((elem) => elem.checked)
        .map((elem) => elem.value);
    var video_formats = Array.from(document.querySelectorAll(".video_formats"))
        .filter((elem) => elem.checked)
        .map((elem) => elem.value);
    var document_formats = Array.from(document.querySelectorAll(".document_formats"))
        .filter((elem) => elem.checked)
        .map((elem) => elem.value);

    OB.API.post(
        "media",
        "formats_save",
        {
            audio_formats: audio_formats,
            video_formats: video_formats,
            image_formats: image_formats,
            document_formats: document_formats,
        },
        function (data) {
            $("#formats_message").obWidget("success", data.msg);
        },
    );
};

OB.Media.metadataAddEditWindow = function (id) {
    OB.UI.openModalWindow("media/metadata_addedit.html");

    $("#metadata_type").change(OB.Media.metadataAddEditTypeChange);
    $("#metadata_select_options").change(OB.Media.metadataAddEditTypeChange);

    var metadata_set_default = null;

    if (id) {
        //T Edit Metadata Field
        $("#metadata_addedit_heading").text(OB.t("Edit Metadata Field"));
        $("#metadata_addedit_id").val(id);

        $.each(OB.Settings.media_metadata, function (index, metadata) {
            if (metadata.id == id) {
                $("#metadata_name").replaceWith($("<span></span>").text(metadata.name));
                $("#metadata_type").replaceWith($("<span data-t></span>").text(metadata.type));
                $("#metadata_addedit_id").after(
                    '<input type="hidden" id="metadata_type" value="' + metadata.type + '">',
                );
                $("#metadata_description").val(metadata.description);
                if (metadata.type == "select" && metadata.settings && metadata.settings.options) {
                    $("#metadata_select_options").val(metadata.settings.options.join("\n"));
                }
                if (metadata.type == "tags" && metadata.settings && metadata.settings.suggestions) {
                    $("#metadata_tag_suggestions").val(metadata.settings.suggestions);
                }
                if (metadata.settings.mode) {
                    document.querySelector("#metadata_mode").value = metadata.settings.mode;
                }
                if (metadata.settings.id3_key) {
                    document.querySelector("#metadata_id3_key").value = metadata.settings.id3_key;
                }
                if (metadata.settings && metadata.settings.default) metadata_set_default = metadata.settings.default;
                return false; // break out of each
            }
        });
    } else {
        //T New Metadata Field
        $("#metadata_addedit_heading").text(OB.t("New Metadata Field"));
        $("#layout_modal_window .edit_only").hide();
    }

    OB.Media.metadataAddEditTypeChange();

    // doing this later because metadataAddEditTypechange needs to run first
    if (metadata_set_default !== null) $("#metadata_default").val(metadata_set_default);
};

OB.Media.metadataAddEditTypeChange = function () {
    var datatype = $("#metadata_type").val();

    // hidden type does not have a default
    if (datatype == "hidden") $(".metadata_default").parent().hide();
    else $(".metadata_default").parent().show();

    $(".metadata_default").hide().removeAttr("id");
    $(".metadata_default_" + datatype)
        .show()
        .attr("id", "metadata_default");

    // handle select field
    var select_value = document.querySelector(".metadata_default_select").value;
    var select_options = document.querySelector("#metadata_select_options").value?.split("\n");
    document.querySelector(".metadata_default_select").options = select_options;
    document.querySelector(".metadata_default_select").value = select_value;

    $("#metadata_select_options")
        .parent()
        .toggle(datatype == "select");

    // handle tag field
    $("#metadata_tag_suggestions")
        .parent()
        .toggle(datatype == "tags");
};

OB.Media.metadataSave = function () {
    var field = {};
    field.id = $("#metadata_addedit_id").val();
    field.name = $("#metadata_name").val();
    field.description = $("#metadata_description").val();
    field.type = $("#metadata_type").val();
    field.select_options = $("#metadata_select_options").val();
    field.tag_suggestions = $("#metadata_tag_suggestions").val();
    field.default = $("#metadata_default").val();
    field.mode = document.querySelector("#metadata_mode").value;
    field.id3_key = document.querySelector("#metadata_id3_key").value;
    field.visibility = document.querySelector("#metadata_visibility").value;

    OB.API.post("metadata", "metadata_save", field, function (response) {
        if (response.status == false) {
            $("#metadata_addedit_message").obWidget("error", response.msg);
        } else {
            OB.UI.closeModalWindow();
            $("#media_settings_metadata_message").obWidget("success", response.msg);
            OB.Settings.getSettings(OB.Media.metadataGet); // refresh the client-side media settings data
        }
    });
};

OB.Media.metadataDelete = function (confirm) {
    var field_id = $("#metadata_addedit_id").val();

    if (!confirm) {
        //T Are you sure you want to delete this field? All media metadata associated with this field will be lost.
        //T Yes, Delete
        //T No, Cancel
        OB.UI.confirm(
            "Are you sure you want to delete this field? All media metadata associated with this field will be lost.",
            function () {
                OB.Media.metadataDelete(true);
            },
            "Yes, Delete",
            "No, Cancel",
            "delete",
        );
    } else {
        OB.API.post("metadata", "metadata_delete", { id: field_id }, function (response) {
            if (response.status == false) {
                $("#metadata_addedit_message").obWidget("error", response.msg);
            } else {
                OB.UI.closeModalWindow();

                //T Metadata field deleted.
                $("#media_settings_metadata_message").obWidget("success", "Metadata field deleted.");
                OB.Settings.getSettings(OB.Media.metadataGet); // refresh the client-side media settings data
            }
        });
    }
};

OB.Media.metadataGet = function () {
    $("#media_settings_metadata tbody").html("");

    if (!OB.Settings.media_metadata.length) {
        //T No custom metadata fields found.
        $("#media_settings_metadata tbody").append(
            '<tr><td colspan="4" data-t>No custom metadata fields found.</td></tr>',
        );
    } else
        $.each(OB.Settings.media_metadata, function (index, metadata) {
            var $tr = $('<tr data-id="' + metadata.id + '"></tr>');
            $tr.append($("<td></td>").text(metadata.description));
            $tr.append($("<td></td>").text(metadata.name));
            $tr.append($("<td data-t></td>").text(metadata.type));
            //T Edit
            $tr.append(
                '<td><button data-t onclick="OB.Media.metadataAddEditWindow(' + metadata.id + ');">Edit</button></td>',
            );
            $("#media_settings_metadata tbody").append($tr);
        });

    $("#media_settings_metadata tbody").sortable();
};

// save metadata field order
OB.Media.metadataSaveOrder = function () {
    var order = [];

    $("#media_settings_metadata tbody tr").each(function (index, element) {
        order.push($(element).attr("data-id"));
    });

    OB.API.post("metadata", "metadata_order", { order: order }, function (data) {
        $("#media_settings_metadata_message").obWidget(data.status ? "success" : "error", data.msg);
        OB.Settings.getSettings(); // refresh the client-side media settings data
    });
};

OB.Media.fieldsGet = function () {
    OB.API.post("metadata", "media_get_fields", {}, function (result) {
        for (var item in result.data) {
            $("#media_fields_" + item + ' input[type="radio"]')
                .filter("[value=" + result.data[item] + "]")
                .prop("checked", true);
        }

        $('#media_fields_dynamic input[type="radio"]')
            .filter("[value=" + result.data["dynamic_content_default"] + "]")
            .prop("checked", true);
        $('#media_fields_dynamic input[type="checkbox"]').prop("checked", result.data["dynamic_content_hidden"]);
    });
};

OB.Media.fieldsSave = function () {
    var post = {};

    post.artist = $('#media_fields_artist input[type="radio"]:checked').val();
    post.album = $('#media_fields_album input[type="radio"]:checked').val();
    post.year = $('#media_fields_year input[type="radio"]:checked').val();
    post.category_id = $('#media_fields_category_id input[type="radio"]:checked').val();
    post.country = $('#media_fields_country input[type="radio"]:checked').val();
    post.language = $('#media_fields_language input[type="radio"]:checked').val();
    post.comments = $('#media_fields_comments input[type="radio"]:checked').val();
    post.dynamic_content_default = $('#media_fields_dynamic input[type="radio"]:checked').val();
    post.dynamic_content_hidden = $('#media_fields_dynamic input[type="checkbox"]').is(":checked");

    OB.API.post("metadata", "media_required_fields", post, function (data) {
        $("#media_settings_fields_message").obWidget(data.status ? "success" : "error", data.msg);
        OB.Settings.getSettings();
    });

    return false;
};

OB.Media.recordingDefaultsSave = function () {
    var post = {};
    post.album = document.querySelector("#recording_defaults_album").value;
    post.year = document.querySelector("#recording_defaults_year").value;
    post.category = document.querySelector("#recording_defaults_genre").category;
    post.genre = document.querySelector("#recording_defaults_genre").genre;
    post.country = document.querySelector("#recording_defaults_country").value;
    post.language = document.querySelector("#recording_defaults_language").value;
    post.comments = document.querySelector("#recording_defaults_comments").value;

    post.custom_metadata = {};
    document.querySelectorAll("#recording_custom_metadata [data-name]").forEach((elem) => {
        post.custom_metadata[elem.dataset.name] = elem.value;
    });

    OB.API.post("metadata", "recording_default_values_save", post, function (data) {
        $("#media_settings_recording_message").obWidget(data.status ? "success" : "error", data.msg);
        OB.Settings.getSettings();
    });

    return false;
};

OB.Media.recordingDefaultsGet = function () {
    // Load custom metadata fields from OB.Settings and insert them into the form.
    const customListElem = document.querySelector("#recording_custom_metadata");
    customListElem.innerHTML = "";

    OB.Settings.media_metadata.forEach((meta) => {
        const labelElem = document.createElement("label");
        labelElem.innerText = meta.name;

        let metaElem = null;
        switch (meta.type) {
            case "text":
                metaElem = document.createElement("ob-field-text");
                break;
            case "select":
                metaElem = document.createElement("ob-field-select");

                meta.settings.options.forEach((option) => {
                    const optionElem = document.createElement("ob-option");
                    optionElem.innerText = option;
                    metaElem.appendChild(optionElem);
                });
                break;
            case "textarea":
                metaElem = document.createElement("ob-field-textarea");
                break;
            case "formatted":
                metaElem = document.createElement("ob-field-formatted");
                break;
            case "integer":
                metaElem = document.createElement("ob-field-number");
                break;
            case "date":
                metaElem = document.createElement("ob-field-date");
                break;
            case "time":
                metaElem = document.createElement("ob-field-time");
                break;
            case "datetime":
                metaElem = document.createElement("ob-field-datetime");
                break;
            case "tags":
                metaElem = document.createElement("ob-field-tags");
                break;
            case "bool":
                metaElem = document.createElement("ob-field-bool");
                break;
            case "media":
                metaElem = document.createElement("ob-field-media");
                metaElem.dataset.single = "";
                break;
            case "playlist":
                metaElem = document.createElement("ob-field-playlist");
                metaElem.dataset.single = "";
                break;
        }

        if (!metaElem) {
            return;
        }

        metaElem.dataset.name = meta.name;
        metaElem.dataset.edit = "";

        if (meta.settings.mode === "required") {
            labelElem.classList.add("required");
        }

        const rowElem = document.createElement("div");
        rowElem.classList.add("fieldrow");

        rowElem.appendChild(labelElem);
        rowElem.appendChild(metaElem);
        customListElem.appendChild(rowElem);
    });

    // Get default values for core metadata fields.
    OB.API.post("metadata", "recording_default_values", {}, function (data) {
        if (data.status) {
            const defaults = data.data;

            document.querySelector("#recording_defaults_album").value = defaults.album;
            document.querySelector("#recording_defaults_year").value = defaults.year;
            document.querySelector("#recording_defaults_genre").category = defaults.category;
            document.querySelector("#recording_defaults_genre").genre = defaults.genre;
            document.querySelector("#recording_defaults_country").value = defaults.country;
            document.querySelector("#recording_defaults_language").value = defaults.language;
            document.querySelector("#recording_defaults_comments").value = defaults.comments;

            ["album", "year", "country", "language", "comments"].forEach((meta) => {
                if (OB.Settings.media_required_fields[meta] === "required") {
                    let elem = document
                        .querySelector("#recording_defaults_" + meta)
                        .closest(".fieldrow")
                        .querySelector("label");
                    elem.classList.add("required");
                }
            });

            let genreLabel = document
                .querySelector("#recording_defaults_genre")
                .closest(".fieldrow")
                .querySelector("label");
            if (OB.Settings.media_required_fields.category_id === "required") {
                genreLabel.classList.add("required");
            }

            // Set custom metadata according to saved settings, and if none can be found, use the default
            // settings for that field (if any).
            OB.Settings.media_metadata.forEach((meta) => {
                const metaFieldElem = document.querySelector(
                    '#recording_custom_metadata [data-name="' + meta.name + '"]',
                );

                if (defaults.custom_metadata && meta.name in defaults.custom_metadata) {
                    metaFieldElem.value = defaults.custom_metadata[meta.name];
                } else {
                    metaFieldElem.value = meta.settings.default;
                }
            });
        }
    });
};
