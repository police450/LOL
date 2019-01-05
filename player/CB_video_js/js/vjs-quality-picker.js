// import QualityPickerButton from './quality-picker-button';

function qualityPickerPlugin() {
    var player = this;
    var tech = this.tech_;

    let SUPPORTED_TRACKS = ["video", "audio", "subtitle"];
    let TRACK_CLASS = {
        video: 'vjs-icon-cog',
        audio: 'vjs-icon-cog',
        subtitle: 'vjs-icon-subtitles'
    };

    tech.on('loadedqualitydata', onQualityData);

    function onQualityData(event, {qualityData, qualitySwitchCallback}) {

        var fullscreenToggle = player.controlBar.getChild('fullscreenToggle');
        player.controlBar.removeChild(fullscreenToggle);

        for (var i=0; i < SUPPORTED_TRACKS.length; i++) {
            var track = SUPPORTED_TRACKS[i];
            var name = track + "PickerButton";
            // videojs.utils.toTitleCase
            name = name.charAt(0).toUpperCase() + name.slice(1);

            var qualityPickerButton = player.controlBar.getChild(name);
            if (qualityPickerButton) {
                qualityPickerButton.dispose();
                player.controlBar.removeChild(qualityPickerButton);
            }

            if (qualityData[track] && qualityData[track].length > 1) {
                qualityPickerButton = new QualityPickerButton(player, {name, qualityList: qualityData[track], qualitySwitchCallback, trackType: track});
                qualityPickerButton.addClass(TRACK_CLASS[track]);

                player.controlBar.addChild(qualityPickerButton);
            }
        }

        if (fullscreenToggle) {
            player.controlBar.addChild(fullscreenToggle);
        }
    }
}

videojs.plugin('qualityPickerPlugin', qualityPickerPlugin);



const VjsButton = videojs.getComponent('MenuButton');

class QualityPickerButton extends VjsButton {

  createMenu() {
    var menu = new QualityMenu(this.player, this.options_);
    var menuItem;
    var options;
    for (var i=0; i < this.options_.qualityList.length; i++) {
      var quality = this.options_.qualityList[i];
      var {qualitySwitchCallback, trackType} = this.options_;
      options = Object.assign({qualitySwitchCallback, trackType}, quality, { selectable: true });

      menuItem = new QualityMenuItem(this.player, options);
      menu.addItem(menuItem);
    }

    return menu;
  }
}

// export default QualityPickerButton;

const VjsMenu = videojs.getComponent('Menu');

class QualityMenu extends VjsMenu {

  addItem(component) {
    super.addItem(component);

    component.on('click', () => {
      let children = this.children();

      for (var i=0; i < children.length; i++) {
        var child = children[i];
        if (component !== child) {
          child.selected(false);
        }
      }

    });
  }

}

// export default QualityMenu;

const VjsMenuItem = videojs.getComponent('MenuItem');

class QualityMenuItem extends VjsMenuItem {
    handleClick() {
        super.handleClick();

        this.options_.qualitySwitchCallback(this.options_.id, this.options_.trackType);
    }
}

// export default QualityMenuItem;
