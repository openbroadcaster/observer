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

// media details page
OB.Media.detailsPage = function (id) {
    OB.API.post("media", "get", { id: id, where_used: true }, function (response) {
        if (response.status == false) return;

        OB.UI.replaceMain("media/details.html", { "data-media_id": id });

        var item = response.data;
        var used = response.data.where_used.used;

        // handle buttons

        // we can download if we're the owner, or we have the download_media permission
        if (OB.Account.user_id == item.owner_id || OB.Settings.permissions.indexOf("download_media") != -1)
            $("#media_details_download")
                .show()
                .click(function () {
                    OB.Media.download(id);
                });

        // we can edit if we have manage_media (manage all media), or we're the owner and can create our own media
        if (item.can_edit) {
            $("#media_details_edit")
                .show()
                .click(function () {
                    OB.Media.editPage(id);
                });

            // we can also manage versions if we additionally have manage_media_versions
            if (OB.Settings.permissions.indexOf("manage_media_versions") != -1)
                $("#media_details_versions")
                    .show()
                    .click(function () {
                        OB.Media.versionPage(id, item.title);
                    });

            // if regular approved media, we can delete. if not, we need manage_media to delete.
            if (
                (item.is_archived == 0 && item.is_approved == 1) ||
                OB.Settings.permissions.indexOf("manage_media") != -1
            )
                $("#media_details_delete")
                    .show()
                    .click(function () {
                        OB.Media.deletePage(id);
                    });
        }

        // we can restore if this is already archived
        if (item.is_archived == 1 && OB.Settings.permissions.indexOf("manage_media") != -1)
            $("#media_details_restore")
                .show()
                .click(function () {
                    OB.Media.unarchivePage(id);
                });

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
            $.each(used, function (index, used_detail) {
                //T playlist
                if (used_detail.where == "playlist")
                    $("#media_details_used ul").append(
                        "<li>" +
                            htmlspecialchars(OB.t("playlist")) +
                            ': <a href="javascript: OB.Playlist.detailsPage(' +
                            used_detail.id +
                            ');">' +
                            htmlspecialchars(used_detail.name) +
                            "</a></li>",
                    );
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
