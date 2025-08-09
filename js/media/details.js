// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

// media details page
OB.MediaDetails = {};
OB.MediaDetails.currentId = null;
OB.MediaDetails.page = function (id) {
    OB.MediaDetails.currentId = id;
    OB.API.post("media", "get", { id: id, where_used: true }, function (response) {
        if (response.status == false) return;

        OB.UI.replaceMain("media/details.html", { "data-media_id": id });

        var item = response.data;
        var used = response.data.where_used.used;

        if (item?.type == "image") {
            OB.API.post("media", "get_properties", { id: id }, function (propertiesResponse) {
                const properties = propertiesResponse.data;

                const mediaDetailsSettings = document.querySelector('.media_details_settings[data-type="image"]');
                mediaDetailsSettings.removeAttribute("hidden");
                const imageRotate = document.createElement("ob-field-image-rotate");
                // set class
                imageRotate.className = "media_details_settings_imagerotate";
                imageRotate.dataset.edit = "true";
                imageRotate.dataset.id = item.id;
                console.log(properties);
                imageRotate.value = imageRotate.offset = properties?.rotate ?? 0;
                mediaDetailsSettings.appendChild(imageRotate);
                document.querySelector(".media_details_settings_save").removeAttribute("hidden");
            });
        } else {
            $('.media_details_settings[data-type="none"]').show();
        }

        // handle buttons

        // we can download if we're the owner, or we have the download_media permission
        if (OB.Account.user_id == item.owner_id || OB.Settings.permissions.indexOf("download_media") != -1) {
            $("#media_details_download").click(function () {
                OB.Media.download(id);
            });
            document.querySelector("#media_details_download").classList.remove("hidden");
        }

        // we can edit if we have manage_media (manage all media), or we're the owner and can create our own media
        if (item.can_edit) {
            $("#media_details_edit").click(function () {
                OB.Media.editPage(id);
            });
            document.querySelector("#media_details_edit").classList.remove("hidden");

            // we can also manage versions if we additionally have manage_media_versions
            if (OB.Settings.permissions.indexOf("manage_media_versions") != -1) {
                $("#media_details_versions").click(function () {
                    OB.Media.versionPage(id, item.title);
                });
                document.querySelector("#media_details_versions").classList.remove("hidden");
            }

            // if regular approved media, we can delete. if not, we need manage_media to delete.
            if (
                (item.is_archived == 0 && item.is_approved == 1) ||
                OB.Settings.permissions.indexOf("manage_media") != -1
            ) {
                $("#media_details_delete").click(function () {
                    OB.Media.deletePage(id);
                });
                document.querySelector("#media_details_delete").classList.remove("hidden");
            }
        }

        // we can restore if this is already archived
        if (item.is_archived == 1 && OB.Settings.permissions.indexOf("manage_media") != -1) {
            $("#media_details_restore").click(function () {
                OB.Media.unarchivePage(id);
            });
            document.querySelector("#media_details_restore").classList.remove("hidden");
        }

        // handle metadata
        $("#media_details_id").text(id);
        $("#media_details_thumbnail").attr("data-id", id);
        $("#media_details_thumbnail")[0].refresh();
        $("#media_details_artist").text(item.artist);
        $("#media_details_title").text(item.title);
        $("#media_details_album").text(item.album);
        $("#media_details_year").text(item.year);
        $("#media_details_category").text(item.category_name);
        $("#media_details_country").text(item.country_name);
        $("#media_details_language").text(item.language_name);
        $("#media_details_genre").text(item.genre_name);
        $("#media_details_comments").text(item.comments);

        // add custom metadata
        OB.Settings.media_metadata.forEach((metadata) => {
            if (metadata.type == "hidden") return;

            var value = item["metadata_" + metadata.name] ?? "";
            if (metadata.type == "tags") value = value.split(",").join(", ");

            const metaElem = document.createElement("div");
            metaElem.className = "fieldrow";
            const labelElem = document.createElement("label");
            labelElem.textContent = metadata.description;
            metaElem.appendChild(labelElem);

            // use custom html element if available
            if (customElements.get("ob-field-" + metadata.type)) {
                const fieldElem = document.createElement("ob-field-" + metadata.type);
                fieldElem.value = value;
                fieldElem.settings = metadata.settings;
                metaElem.appendChild(fieldElem);
            }

            // fallback to span
            else {
                const spanElem = document.createElement("span");
                spanElem.textContent = value;
                metaElem.appendChild(spanElem);
            }

            document.querySelector("#media_details_metadata").appendChild(metaElem);
        });

        // remove unused metadata
        $.each(OB.Settings.media_required_fields, function (field, status) {
            field = field.replace(/_id$/, "");
            if (status == "disabled")
                $("#media_details_" + field)
                    .parent()
                    .hide();
            if (status == "disabled" && field == "category") $("#media_details_genre").parent().hide();
        });

        //T Archived
        if (item.is_archived == 1) $("#media_details_approval").text(OB.t("Archived"));
        //T Approved
        else if (item.is_approved == 1) $("#media_details_approval").text(OB.t("Approved"));
        else $("#media_details_approval").text(OB.t("Not Approved"));

        //T Yes
        if (item.is_copyright_owner == 1) $("#media_details_copyright").text(OB.t("Yes"));
        //T No
        else $("#media_details_copyright").text(OB.t("No"));

        //T Private
        if (item.status == "private") $("#media_details_visibility").text(OB.t("Private"));
        //T Visible
        else if (item.status == "visible") $("#media_details_visibility").text(OB.t("Visible"));
        //T Public
        else $("#media_details_visibility").text(OB.t("Public"));

        //T Yes
        if (item.dynamic_select == 1) $("#media_details_dynamic").text(OB.t("Yes"));
        //T No
        else $("#media_details_dynamic").text(OB.t("No"));

        $("#media_details_created").text(format_timestamp(item.created));
        $("#media_details_updated").text(format_timestamp(item.updated));

        $("#media_details_uploader").text(item.owner_name);

        // handle 'where used';

        //T Media is not in use.
        if (used.length == 0) $("#media_details_used").append(OB.t("Media is not in use"));
        else {
            const used_playlists = [];

            $.each(used, function (index, used_detail) {
                //T playlist
                if (used_detail.where == "playlist" || used_detail.where == "playlist_voicetrack") {
                    // if already in used_playlists, skip
                    if (used_playlists.includes(used_detail.id)) return true;

                    // track as already outputted
                    used_playlists.push(used_detail.id);

                    // output
                    $("#media_details_used ul").append(
                        "<li>" +
                            htmlspecialchars(OB.t("playlist")) +
                            ': <a href="javascript: OB.Playlist.detailsPage(' +
                            used_detail.id +
                            ');">' +
                            htmlspecialchars(used_detail.name) +
                            "</a></li>",
                    );
                }
                //T dynamic playlist
                if (used_detail.where == "playlist_dynamic")
                    $("#media_details_used ul").append(
                        "<li>*" +
                            htmlspecialchars(OB.t("dynamic playlist")) +
                            ': <a href="javascript: OB.Playlist.detailsPage(' +
                            used_detail.id +
                            ');">' +
                            htmlspecialchars(used_detail.name) +
                            "</a></li>",
                    );
                //T station ID
                if (used_detail.where == "player")
                    $("#media_details_used ul").append(
                        "<li>" +
                            htmlspecialchars(OB.t("station ID")) +
                            ": " +
                            htmlspecialchars(used_detail.name) +
                            "</li>",
                    );
                //T priority broadcast
                if (used_detail.where == "alert")
                    $("#media_details_used ul").append(
                        "<li>" +
                            htmlspecialchars(OB.t("priority broadcast")) +
                            ": " +
                            htmlspecialchars(used_detail.name) +
                            "</li>",
                    );
                //T show for player
                if (used_detail.where == "show")
                    $("#media_details_used ul").append(
                        "<li>" +
                            htmlspecialchars(OB.t("show for player")) +
                            ": " +
                            htmlspecialchars(used_detail.name) +
                            "</li>",
                    );
            });

            //T Indicates possible dynamic selection.
            $("#media_details_used").append(
                "<p>* " + htmlspecialchars(OB.t("Indicates possible dynamic selection.")) + "</p>",
            );
        }

        $("#media_details_table").show();
        $("#media_details_used").show();
    });
};

OB.MediaDetails.saveSettings = async function () {
    $("#media_details_settings_saved").hide();

    const rotateField = document.querySelector(".media_details_settings_imagerotate");

    if (rotateField) {
        const data = {};
        data.properties = {
            rotate: rotateField.value,
        };

        OB.API.post(
            "media",
            "save_properties",
            { id: OB.MediaDetails.currentId, properties: data.properties },
            function (response) {
                if (response.status == true) {
                    $("#media_details_settings_saved").show();
                }
            },
        );
    }
};
