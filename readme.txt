=== BIM-ba ===
Contributors: andywar65
Donate link: http://www.andywar.net/wordpress-plugins/donate
Tags: BIM, 3D, VR, architecture, modeling
Requires at least: 4.1
Tested up to: 4.7.4
Stable tag: 2.2.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

BIM-ba is a very basic BIM (Building Information Modeling). You can model an apartment and visit it in Virtual Reality.

== Description ==

BIM-ba is a very basic BIM at it's first steps.
You can model rooms and assemble them into an apartment, apply images and materials, add some furniture, all in the admin.
You will then be able to navigate into your creation directly from the WordPress front end, and gather some useful dimensional information.  

Activate the plugin and find the BIM-ba menu in the admin screen. Add a 3D-ambient custom post type as your first room to model.
Name the room and add some information, if you want. 3D-ambient CPT is hyerarchical, so nest the room (child) into the apartment (parent).

Below the editor you will find a series of metaboxes. First you can provide general information about your ambient, height and materials.
If you have an image for your floor you can add it here. Materials come from a dropdown menu (see below).

Once you have set the general rules for your ambient, let's start building the walls. Just specify direction of wall and length.
Add consecutive walls, as many as you need to enclose the ambient. Feel free to add images and materials to the walls, and open doors and windows.

Some furniture is available, select a wall and place the furniture relating it to wall origin.

Now save and click on "View 3D Ambient". On the front end, below title and description you will see a window showing your room in perspective.
Click and drag with the mouse to look around. Use WASD keys to navigate. Click on the visor icon to watch full screen.
On some devices image will be split in two, with a stereoscopic effect.

Materials are stored into another custom post type (3D-material). 
By now you can only provide a 1x1 meter image and the color of the material, but later on other attributes will be available.

Ambients are placed relative to their parent, using the Global Positioning metabox.
If you view the parent ambient, all it's children will be rendered too. 

Plugin uses the A-Frame JavaScript library to render the model (version 0.5.0), and CMB2 / CMB2-Conditionals plugins to handle the metaboxes.
All dependencies are packed within BIM-ba, so you're not forced to install them.

Additional instructions may be found here: http://www.bim-ba.net/

Since version 1.0.2 a very basic structural analisys module is available using the shortcode [steel_deck] in your posts and pages. Shortcode provides calculation
of a steel deck given size, materials, profiles and design criterion.

Prior to version 2.0.0 the plugin existed only as a Studio Management tool. This part has been discarded for plugin now focuses on the modeling aspect.
Previous files are still there, but they are not recalled by the plugin.

== Installation ==

1. Download and unzip `bim-ba` folder, then upload it to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Nothing else

== Frequently Asked Questions ==

= Does the plugin work on multisites? =

Yes, it does.

= Does it work on all themes? =

Tested on Twenty Sixteen, but it should work on any theme.

== Screenshots ==

1. This is how the 'BIM-ba' menu appears.
2. Metaboxes of a 3D Ambient entry. 
3. Virtual Reality in action on the front end.
4. Dimension data is available on the front end.

== Changelog ==

= 2.2.4 =
* Materials for objects
* Objects refer to Wall by name (sorry, this may disrupt old projects)
* Walls, Axis and Wall Elements receive unique name
* 3D Plan (still in experimental phase) rendered in back end

= 2.2.3 =
* Fixing CMB2 library
* Experimental 3D Plan
* Better handling of Module object

= 2.2.2 =
* General code clean up
* Experimental 3D Element
* CMB2 library version 2.2.4
* Fixed a bug in global positioning.
* You can now duplicate 3D Ambient and Material CPTs.

= 2.2.1 =
* Small fix in window rendering.
* Objects are parametric. Cylinder object added.
* You can choose what child to render along with parent.

= 2.2.0 =
* A-Frame library 0.5.0 with text integration!
* Camera can fly
* New generic materials, custom Module object.
* You can leave written notes.
* Outer frames for doors, new animations for doors and windows.
* Indipendent ceiling image / material.
* Vertical displacement for child 3D ambients.

= 2.1.1 =
* 3D ambient not visible for password protected posts.

= 2.1.0 =
* Colors are associated only to materials, not to single entities.
* Grouped Room Settings, Material and Global post meta.
* Added 3D Material categories and generic material items.
* Added furniture and some animations to doors.
* Cleaned up CMB2 package for I18n reasons.

= 2.0.1 =
* I18n and Italian translation available.

= 2.0.0 =
* Studio Management abandoned and 3D ambient modeling first introduced.

= 1.0.2 =
* Steel deck structural analisys available.

= 1.0.1 =
* Italian translation available.

= 1.0 =
* First release.

== Upgrade Notice ==

= 2.2.x =

= 2.2.3 =
* Fixing CMB2 library

= 2.2.2 =
* New CMB2 library and code clean up.

= 2.2.1 =
* Small fix in window rendering.

= 2.2.0 =
* New A-Frame library and overall improvements.

= 2.1.1 =
* 3D ambient not visible for password protected posts.

= 2.1.0 =
* Important changes in color handling. Requires reactivation

= 2.0.1 =
* I18n and Italian translation available.

= 2.0.0 =
* Studio Management abandoned and 3D ambient modeling first introduced.

= 1.0.2 =
* Steel deck structural analisys available.

= 1.0.1 =
* Italian translation available.

= 1.0 =
* First release.