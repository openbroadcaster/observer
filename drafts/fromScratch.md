---
layout: default
title: fromScratch
---


# Starting from scratch

Make sure your environment meets the [prerequisites](#minimumSpec) before installing OpenBroadcaster Player. If you have administrative privileges, simply cut, paste and run the code shown below for the desired Player operating environment. Then, download [Player](https://github.com/openbroadcaster/OBPlayer). For more detailed instructions on setting up Openbroadcaster Player on an Ubuntu platform.

### Minimum Hardware Platform

<a name="minimumSpec"></a>

| Item | Description |
|:--------------|:------------|
| Processor  | ARM Cortex, Intel Celeron or better |
| Memory | 2 GB RAM |
| Storage | Minimum 1GB |
| Display | Minimum 640x480 screen resolution. HDMI for HD content. |
| Audio | Analog Input\Output and\or Digital HDMI, SPDIF. Gstreamer uses JACK or Pulse to send the audio output to an ICE cast streaming server. |
  
*No display actually required unless displaying Video or Images*

OBPlayer should be on a \*nix system (Debian 7 with xfce or xubuntu 14.04 LTS for example).

### Software Dependencies

##### OBPlayer Core

    python python-argparse python-pycurl python-openssl python-apsw python-magic python-dateutil python-requests python-gi python-gi-cairo python-gst-1.0 python-serial gir1.2-gtk-3.0 gir1.2-gdkpixbuf-2.0 gir1.2-pango-1.0 gir1.2-gstreamer-1.0 gir1.2-gst-plugins-base-1.0 gstreamer1.0-alsa gstreamer1.0-pulseaudio gstreamer1.0-tools gstreamer1.0-libav gstreamer1.0-plugins-base gstreamer1.0-plugins-good gstreamer1.0-plugins-bad gstreamer1.0-plugins-ugly gstreamer1.0-vaapi mesa-vdpau-drivers ubuntu-restricted-addons ubuntu-restricted-extras git ntp ssh

##### OBPlayer Everything (Streaming Production Workstation)

    python python-argparse python-pycurl python-openssl python-apsw python-magic python-dateutil python-requests python-gi python-gi-cairo python-serial python-gst-1.0 gir1.2-gtk-3.0 gir1.2-gdkpixbuf-2.0 gir1.2-pango-1.0 gir1.2-gstreamer-1.0 gir1.2-gst-plugins-base-1.0 gstreamer1.0-tools gstreamer1.0-alsa gstreamer1.0-pulseaudio gstreamer1.0-libav gstreamer1.0-plugins-base gstreamer1.0-plugins-good gstreamer1.0-plugins-bad gstreamer1.0-plugins-ugly gstreamer1.0-vaapi mesa-vdpau-drivers ubuntu-restricted-addons ubuntu-restricted-extras cifs-utils ssh ntp git vlc audacity dia inkscape libreoffice vino icecast2 pavucontrol pulseaudio-utils handbrake jack qjackctl xvfb libav-tools curl remmina mediainfo-gui gnome-disk-utility alsa-tools-gui

##### OBPlayer Alerting Espeak

    espeak mbrola mbrola-en1 mbrola-us1 mbrola-us2 mbrola-us3 mbrola-fr1 mbrola-fr4

