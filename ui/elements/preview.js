import { OBElement } from "../base/element.js";
import { html, render } from "../vendor.js";

class OBElementPreview extends OBElement {
    #init;

    #itemId;
    #itemType;
    #queue;
    #imageTimeout;

    #imageWidth;
    #imageHeight;

    #videojsPlayer;

    #currentPlaylist;

    connectedCallback() {
        if (this.#init) {
            return;
        }

        this.loadDeps().then(() => {
            this.#init = true;

            this.addEventListener("dragstart", this.onDragStart.bind(this));
            this.addEventListener("dragend", this.onDragEnd.bind(this));

            this.renderComponent().then(() => {
                this.#imageWidth = this.root.querySelector("#preview").offsetWidth;
                this.#imageHeight = this.root.querySelector("#preview").offsetHeight;

                this.root.querySelector("#drag").addEventListener("mouseup", this.onMouseUp.bind(this));
            });
        });
    }

    async loadDeps() {
        let style = document.createElement("link");
        style.rel = "stylesheet";
        style.href = "../../node_modules/video.js/dist/video-js.min.css";
        style.type = "text/css";
        this.root.appendChild(style);

        await new Promise((resolve, reject) => {
            style.onload = resolve();
            style.onerror = reject(new Error("Error loading video.js stylesheet"));
        });
    }

    async renderComponent() {
        if (this.root.querySelector("#drag audio, #drag video")) {
            let mediaElem = this.root.querySelector("#drag audio, #drag video");

            mediaElem.pause();
            mediaElem.setAttribute("src", "");
            mediaElem.removeAttribute("src");
        }

        if (this.#videojsPlayer) {
            this.#videojsPlayer.dispose();
            this.#videojsPlayer = null;
        }

        let queueFirst = null;
        let queueLast = null;
        if (this.#queue) {
            queueFirst = this.#itemId === 0;
            queueLast = this.#itemId === this.#queue.length - 1;

            this.#itemType = this.#queue[this.#itemId].type;
        }

        const sidebarLeft = document.querySelector("body").classList.contains("sidebar-left");
        if (sidebarLeft) this.root.classList.add("-flipped");

        render(
            html`
                <div id="preview" class="${sidebarLeft && "-flipped"}">
                    <div id="drag">
                        ${this.#itemType === "audio" || this.#itemType === "video"
                            ? html`<video-js></video-js> `
                            : html``}
                        ${this.#itemType === "image" || this.#itemType === "document"
                            ? html`
                                  <ob-element-thumbnail data-id=${this.#queue[this.#itemId].id}></ob-element-thumbnail>
                              `
                            : html``}
                    </div>
                    <div id="queue" class="hidden">
                        <button onclick=${() => this.playlistRefresh()}>Refresh</button>
                        ${this.#queue &&
                        this.#queue.map(
                            (queueItem, index) => html`
                                <span
                                    onclick=${() => this.queuePlay([index])}
                                    data-id=${index}
                                    class="${index == this.#itemId && html`current`}"
                                    >${queueItem.artist} - ${queueItem.title}</span
                                >
                            `,
                        )}
                    </div>
                </div>
                ${this.#queue &&
                this.#queue[this.#itemId] &&
                html`
                    <div id="current">
                        <span class="buttons queue"><button onclick=${() => this.queueToggleView()}>☰</button></span>
                        <span class="buttons">
                            ${queueFirst
                                ? html` <button disabled>«</button> `
                                : html` <button onclick=${() => this.queuePrevious()}>«</button> `}
                        </span>
                        <span class="title"
                            >${this.#queue[this.#itemId].artist} - ${this.#queue[this.#itemId].title}</span
                        >
                        <span class="buttons">
                            ${queueLast
                                ? html` <button disabled>»</button> `
                                : html` <button onclick=${() => this.queueNext()}>»</button> `}
                        </span>
                    </div>
                `}
            `,
            this.root,
        );

        const videoElem = this.root.querySelector("video-js");
        if (videoElem) {
            let elem = this;
            let mediaId = this.#queue[this.#itemId].id;
            let mediaUrl = this.#queue[this.#itemId].stream;
            let captions = this.#queue[this.#itemId].captions;
            let thumbnail = this.#queue[this.#itemId].thumbnail;
            let poster = null;

            let blob = false;
            if (thumbnail) {
                blob = await OB.API.request({
                    endpoint: "downloads/media/" + mediaId + "/thumbnail/",
                    raw: true,
                });
            }
            if (blob) {
                poster = URL.createObjectURL(blob);
            } else {
                poster = "/images/circle.svg";
            }

            // if ends in stream, we have stream
            const stream = mediaUrl.endsWith("stream/");

            switch (this.#itemType) {
                case "audio":
                    this.#videojsPlayer = videojs(videoElem, {
                        controls: true,
                        preload: "auto",
                        poster: poster,
                        audioPosterMode: true,
                    });

                    this.#videojsPlayer.ready(function () {
                        this.on("ended", function () {
                            elem.queueNext(true);
                        });
                    });

                    this.#videojsPlayer.src({ type: stream ? "application/x-mpegURL" : "audio/mpeg", src: mediaUrl });

                    break;
                case "video":
                    const videoJsOptions = {
                        controls: true,
                        preload: "auto",
                        poster: poster,
                    };

                    if (captions) {
                        videoJsOptions.tracks = [
                            {
                                src: captions,
                                kind: "subtitles",
                                srclang: "en",
                                label: "English",
                            },
                        ];
                    }

                    this.#videojsPlayer = videojs(videoElem, videoJsOptions);

                    this.#videojsPlayer.ready(function () {
                        this.on("ended", function () {
                            elem.queueNext(true);
                        });
                    });

                    this.#videojsPlayer.src({ type: stream ? "application/x-mpegURL" : "video/mp4", src: mediaUrl });

                    break;
            }
        }
    }

    scss() {
        return `
            :host {
                display: inline-block;

                #preview {
                    text-align: center;
                    height: 200px;
                    background-color: rgba(0, 0, 0, 0.3);
                    border-radius: 5px;
                    width: 370px;

                    img {
                        max-width: 100%;
                        max-height: 100%;
                    }
                }

                #drag {
                    color: #7f7d8b;
                    font-size: 6em;
                    line-height: 200px;
                    border-radius: 5px;
                    border: 2px solid rgba(0,0,0,0);
                    box-sizing: border-box;
                    height: 200px;

                    &::after {
                        font-family: "Font Awesome 5 Free";
                        content: "\\f144"
                    }

                    &.dragging {
                        border: 2px dashed #e09529;
                    }

                    &:not(:empty)::after {
                        content: "";
                    }
                }

                video-js {
                    width: 100%;
                    height: 100%;
                    border-radius: 5px;
                }

                #queue {
                    &.hidden {
                        display: none;
                    }

                    display: flex;
                    flex-direction: column;
                    position: relative;
                    right: calc(100% + 0.5em);
                    bottom: 100%;
                    height: 100%;

                    background-color: rgba(0, 0, 0, 0.8);
                    border-radius: 5px;
                    border: 2px solid rgba(0, 0, 0, 0);
                    box-sizing: border-box;

                    font-size: 13px;
                    text-align: left;
                    padding: 0.5em;
                    color: #eeeeec;

                    overflow: scroll;
                    scrollbar-width: thin;

                    .current {
                        background-color: rgba(0, 0, 0, 0.4);
                    }
                }

                #current {
                    display: flex;
                    margin-top: 0.5em;

                    span.buttons {
                        white-space: nowrap;
                    }

                    .title { flex-grow: 1; text-align: center; padding: 0 5px; }
                }

                #root.-flipped
                {
                    #queue {
                        right: auto;
                        left: calc(100% + 0.5em);
                    }

                    #current {
                        .buttons.queue { order: 1; }
                    }
                }
            }
        `;
    }

    onDragStart() {
        this.root.querySelector("#drag").classList.add("dragging");
    }

    onDragEnd() {
        this.root.querySelector("#drag").classList.remove("dragging");
    }

    onMouseUp() {
        if (!window.dragHelperData) {
            return false;
        }

        this.#queue = [];
        this.#itemId = null;
        this.#itemType = null;
        this.#currentPlaylist = null;

        if (window.dragHelperData[0].classList.contains("sidebar_search_playlist_result")) {
            this.#resolvePlaylist(window.dragHelperData[0].dataset.id);
        } else if (window.dragHelperData[0].classList.contains("sidebar_search_media_result")) {
            Object.values(window.dragHelperData).forEach((item) => {
                if (!item.dataset) {
                    return;
                }

                let queueItem = {
                    id: item.dataset.id,
                    type: item.dataset.type,
                    title: item.dataset.title,
                    artist: item.dataset.artist,
                    stream: JSON.parse(item.dataset.stream),
                    captions: JSON.parse(item.dataset.captions),
                };

                if (item.dataset.type === "image") {
                    // Set image duration to 3 for the preview queue when dragging individual media
                    // items, as individual images outside of a playlist do not have a duration set.
                    queueItem.duration = 3;
                }

                this.#queue.push(queueItem);
            });

            if (this.#queue.length > 0) {
                this.#itemId = 0;
                this.#itemType = this.#queue[0].type;
            }

            this.refresh();
        }
    }

    queueToggleView() {
        this.root.querySelector("#queue").classList.toggle("hidden");
    }

    queuePrevious() {
        clearTimeout(this.#imageTimeout);

        if (this.#itemId === 0) {
            this.#itemId = this.#queue.length - 1;
        } else {
            this.#itemId--;
        }

        this.renderComponent();
    }

    queueNext(autoplay = false) {
        clearTimeout(this.#imageTimeout);

        if (this.#itemId === this.#queue.length - 1) {
            this.#itemId = 0;
        } else {
            this.#itemId++;
        }

        let elem = this;
        this.renderComponent().then(() => {
            // Autoplay if next item in queue (which passes autoplay = true to method),
            // itemId isn't 0 (this implies we've finished the queue), and there's a
            // video.js player available.
            if (autoplay && elem.#itemId !== 0 && elem.#videojsPlayer) {
                elem.#videojsPlayer.ready(function () {
                    this.on("canplay", function () {
                        elem.#videojsPlayer.play();
                    });
                });
                // Otherwise, do the same checks as before, but for images (so not using the
                // video.js player), use the image duration set in the playlist before moving
                // to the next item. Note that this is hardcoded to a few seconds for individual
                // media items (a duration isn't provided outside of playlists).
            } else if (autoplay && elem.#itemId !== 0 && elem.#itemType === "image") {
                this.#imageTimeout = setTimeout(function () {
                    elem.queueNext(true);
                }, elem.#queue[elem.#itemId].duration * 1000);
            }
        });
    }

    queuePlay(index) {
        clearTimeout(this.#imageTimeout);

        this.#itemId = index;
        this.renderComponent();
    }

    playlistRefresh() {
        if (!this.#currentPlaylist) {
            return;
        }

        let playlistId = this.#currentPlaylist;

        this.#queue = [];
        this.#itemId = null;
        this.#itemType = null;
        this.#currentPlaylist = null;

        this.#resolvePlaylist(playlistId);
    }

    #resolvePlaylist(id) {
        let elem = this;

        OB.API.post("playlists", "resolve", { id: id }, function (response) {
            if (response.data) {
                response.data.forEach((item) => {
                    let queueItem = {
                        id: item.id,
                        type: item.media_type,
                        title: item.title,
                        artist: item.artist,
                        stream: item.stream,
                        captions: item.captions,
                    };

                    if (item.media_type === "image") {
                        queueItem.duration = item.duration;
                    }

                    elem.#queue.push(queueItem);
                });

                if (elem.#queue.length > 0) {
                    elem.#itemId = 0;
                    elem.#itemType = elem.#queue[0].type;
                }

                elem.#currentPlaylist = id;
                elem.renderComponent();
            }
        });
    }
}

customElements.define("ob-element-preview", OBElementPreview);
