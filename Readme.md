# Social Login for WordPress

This plugin enhances user experience by simplifying the registration and login process on your WordPress site. Users can effortlessly register or log in with a single click using their Google or Facebook account.

# Setting Up the Plugin
After installing and activating the plugin on your site, navigate to the "Social Login Settings" menu item. From here, you can:

- Enable or disable Google and Facebook login options.
- Specify the default user role for users who register with Google or Facebook.
- Add your App ID for Facebook and Google.

![Settings Page](https://drive.google.com/uc?export=download&id=1q-aAf6vXMYaExWutTRSsKyTxZx6HDWdy)

To obtain your App ID, visit the following URLs:

- [Facebook](https://developers.facebook.com/apps/)
  - Make sure to enable the JavaScript SDK module and add your site URL in OAuth redirect and Allowed domains for JDK settings.
- [Google](https://console.cloud.google.com/apis/credentials)
  - Add your site URL to the "Authorized JavaScript origins" setting. For "Authorized redirect URIs," use the following site URL: `https://your-site-url/wp-json/d3v/v1/social-login`


# Additional Features
In addition to simplifying the login process, this plugin offers the following features:
- Integration with custom login pages: Use the shortcode `[d3v_social_login]` to add social login buttons to any custom login page you've created.
- Built-in profile page: Create a page in WordPress using your preferred page editor and add the shortcode `[d3v_profile]`.

  ![profile page](https://drive.google.com/uc?export=download&id=1i1d2etdTvNknDA93BloO_QIIl0gtxkFs)

