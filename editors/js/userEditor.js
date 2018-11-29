/**
* This file is part of the Research Environment for Ancient Documents (READ). For information on the authors
* and copyright holders of READ, please refer to the file AUTHORS in this distribution or
* at <https://github.com/readsoftware>.
*
* READ is free software: you can redistribute it and/or modify it under the terms of the
* GNU General Public License as published by the Free Software Foundation, either version 3 of the License,
* or (at your option) any later version.
*
* READ is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
* without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
* See the GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License along with READ.
* If not, see <http://www.gnu.org/licenses/>.
*/

/**
* editors userEditor object
*
* handles user login, logout and preference UI
*
* @author      Stephen White  <stephenawhite57@gmail.com>
* @copyright   @see AUTHORS in repository root <https://github.com/readsoftware/read>
* @link        https://github.com/readsoftware
* @version     1.0
* @license     @see COPYING in repository root or <http://www.gnu.org/licenses/>
* @package     READ Research Environment for Ancient Documents
* @subpackage  Editors
*/

var EDITORS = EDITORS || {};


/**
* put your comment there...
*
* @type Object
*/

EDITORS.UserVE =  function(userCfg) {
  this.config = userCfg;
  this.hdrDiv = userCfg['userHdrDiv'] ? userCfg['userHdrDiv']:null;
  this.userDiv = userCfg['userVEDiv'] ? userCfg['userVEDiv']:null;
  this.dataMgr = userCfg['dataMgr'] ? userCfg['dataMgr']:null;
  this.layoutMgr = userCfg['layoutMgr'] ? userCfg['layoutMgr']:null;
  this.username = userCfg['username'] && userCfg['username'] != "unknown" ? userCfg['username']:null;
  this.init();
  return this;
};

/**
* put your comment there...
*
* @type Object
*/
EDITORS.UserVE.prototype = {

/**
* put your comment there...
*
*/

  init: function() {
    DEBUG.traceEntry("userVE.init"," user is "+this.username);
    var userVE = this,
        signinLink;
    if (!this.userDiv || !this.hdrDiv) {
      msg = "error trying to initialise user editor UI";
      DEBUG.log("err",msg);
      alert(msg);
    }
    this.loadHeader();
    if (this.username && this.username != "Guest") {
      this.loadUserInfoUI();
    } else {
      this.loadSigninUI();
    }
    DEBUG.traceExit("userVE.init"," user is "+this.username);
  },


/**
* put your comment there...
*
*/

  loadHeader: function(){
      var userVE = this,
          headerImgDiv = $('<div id="userImgDiv" />'),
          userNameDiv = $('<div id="userNameDiv" />');
      if (this.username) {
        userNameDiv.text(this.username);
        if (this.userData && this.userData.iconURL) {
          headerImgDiv.html('<img src="'+this.userData.iconURL+'" />');
        } else {
           headerImgDiv.html('<span class="fontIcon_in">&#x1F464;</span>');
        }
      } else {
        userNameDiv.text("Guest");
        headerImgDiv.html('<span class="fontIcon_out">&#x1F464;</span>');
      }
      this.hdrDiv.html("");
      this.hdrDiv.append(headerImgDiv);
      this.hdrDiv.append(userNameDiv);
      this.updateEditInfo();
  },


/**
* put your comment there...
*
*/

  loadSigninUI: function(){
    DEBUG.traceEntry("userVE.loadSigninUI"," user is "+this.username);
    var userVE = this;
    //create sign in UI
    if (this.signInUI) {
      delete this.signInUI;
    }
    this.signInUI = $('<div id="signinForm" class="signinUI">' +
                      '<div class="label-form">Sign in</div>' +
                      '<div class="label-input">Username</div>' +
                      '<div><input type="text" id="userName" class="text-input" /></div>' +
                      '<div class="label-input">Password</div>' +
                      '<div><input type="password" id="userPassword" class="text-input" /></div>' +
                      '<div><input type="checkbox" id="staySignedIn" /><label for="staySignedIn">Stay signed in</label></div>' +
                      '<input type="button" value="Sign in" id="signinButton" />' +
                      '<div id="signinHelpLink">Sign in help</div>' +
                      '<div id="errorMsg"></div>' +
                      '<div id="newAccountLink">Sign up for a new account</div>' +
                      '</div>');
    $('#signinButton',this.signInUI).bind('click', function() {userVE.validateSignInUI();});
    $('#signinHelpLink',this.signInUI).bind('click', function() {userVE.showSignInHelp();});
    $('#newAccountLink',this.signInUI).bind('click', function() {userVE.loadNewAccountUI();});
    //display UI
    $('#userName',this.signInUI).html("");
    $('#userPassword',this.signInUI).html("");
    $('#errorMsg',this.signInUI).html("");
    this.userDiv.html("");
    this.userDiv.append(this.signInUI);
    DEBUG.traceExit("userVE.loadSigninUI"," user is "+this.username);
  },


/**
* put your comment there...
*
*/

  validateSignInUI: function(){
    DEBUG.traceEntry("userVE.validateSignInUI"," user editor");
    var UserName = $('#userName',this.signInUI),
        uname = UserName.val(),
        UserPassword = $('#userPassword',this.signInUI),
        userpwd = UserPassword.val(),
        ErrorMsg = $('#errorMsg',this.signInUI);
    if (!uname || uname.length <3 || uname.length > 20) {
      ErrorMsg.html("Username is required and must be from 3 to 20 charaters!");
      UserName.focus();
    } else if (!userpwd || userpwd.length <7 || userpwd.length > 20) {
      ErrorMsg.html("Password is required and must be from 7 to 20 charaters!");
      UserPassword.focus();
    }else{
      ErrorMsg.html("");
      this.login(uname,userpwd,$('#staySignedIn',this.signInUI).prop('checked'));
    }
    DEBUG.traceExit("userVE.validateSignInUI"," user editor");
  },


/**
* put your comment there...
*
*/

  showSignInHelp: function(){
    alert("sign in help underconstuction");
  },


/**
* put your comment there...
*
* @param username
* @param password
* @param stayLoggedIn
*/

  login: function(username, password, stayLoggedIn){
    var userVE = this;
    DEBUG.traceEntry("userVE.login"," user editor");
//    DEBUG.log("gen""in login user editor username= " + username + " password= " + password + (stayLoggedIn?" with persistent session":""));
    //call login service
    $.ajax({
        dataType: 'json',
        url: basepath+'/services/login.php?db='+dbName+
                  "&username=" + username + "&password=" + password +
                  (stayLoggedIn?"&persist=1":""),
        asynch: false,
        success: function (data, status, xhr) {
                   if (data && data.error) {
                     $('#errorMsg',userVE.signInUI).html(data.error);
                   } else {
                     userVE.username = username;
                     userVE.dataMgr.username = username;
                     userVE.userData = data;
                     userVE.loadHeader();
                     userVE.loadUserInfoUI();
                     userVE.updateEditInfo();
                     userVE.layoutMgr.resetLayoutManager();
                     userVE.layoutMgr.closeUserPanel();
                     userVE.layoutMgr.refresh("search");
                   }
                 },// end success cb
        error: function (xhr,status,error) {
                 // show login error msg
                 $('#errorMsg',userVE.signInUI).html("Invalid Username or Password!");
               }
    });// end ajax
    DEBUG.traceExit("userVE.login"," user editor");
  },


/**
* put your comment there...
*
*/

  logout: function(){
    var userVE = this;
    DEBUG.traceEntry("userVE.logout"," user editor username= " + this.username );
    //call logout service
    $.ajax({
        dataType: 'json',
        url: basepath+'/services/logout.php?db='+dbName,
        asynch: false,
        success: function (data, status, xhr) {
                   delete userVE.username;
                   userVE.dataMgr.username = null;
                   delete userVE.userData;
                   delete userVE.userPreference;
                   userVE.loadHeader();
                   userVE.updateEditInfo();
                   userVE.loadSigninUI();
                   userVE.layoutMgr.resetLayoutManager();
                   userVE.layoutMgr.closeUserPanel();
                   userVE.layoutMgr.refresh("landing");
                 },// end success cb
        error: function (xhr,status,error) {
                 // show login error msg
                 alert("logout failed!");
               }
    });// end ajax
    DEBUG.traceExit("userVE.logout"," user editor username= " + this.username );
  },


/**
* put your comment there...
*
* @param userpwd
* @param newpwd
*/

chngPwd: function(userpwd, newpwd){
  var userVE = this;
  DEBUG.traceEntry("userVE.login"," user editor");
  //call change pwd service
  $.ajax({
      dataType: 'json',
      url: basepath+'/services/changePwd.php?db='+dbName+
                "&password1=" + userpwd + "&password2=" + newpwd,
      asynch: true,
      success: function (data, status, xhr) {
                 if (data && data.error) {
                   $('#errorMsg',userVE.chngPwdUI).html(data.error);
                 } else {
                   userVE.loadHeader();
                   userVE.loadUserInfoUI();
                   userVE.layoutMgr.closeUserPanel();
                 }
               },// end success cb
      error: function (xhr,status,error) {
               // show login error msg
               $('#errorMsg',userVE.signInUI).html("Invalid Change Request!");
             }
  });// end ajax
  DEBUG.traceExit("userVE.login"," user editor");
},


/**
* put your comment there...
*
*/

  getUsername: function(){
    return this.username;
  },


/**
* put your comment there...
*
*/

  isLoggedIn: function(){
    return (this.username != null && this.username != "Guest");
  },


/**
* put your comment there...
*
*/

  loadNewAccountUI: function(){
    alert("new account is underconstuction");
  },


/**
* put your comment there...
*
*/

loadSettingsUI: function(){
  alert(" Settings UI is underconstuction");
},


/**
* put your comment there...
*
*/

loadforgotPwdUI: function(){
  alert(" Forgot Password UI is underconstuction");
},


/**
* put your comment there...
*
*/

  loadChangePasswordUI: function(){
    DEBUG.traceEntry("userVE.loadChangePasswordUI"," user is "+this.username);
    var userVE = this;
    //create sign in UI
    if (this.chngPwdUI) {
      delete this.chngPwdUI;
    }
    this.chngPwdUI = $('<div id="chngPwdForm" class="chngPwdUI">' +
                            '<div class="label-form">Change Password</div>' +
                            '<div class="label-input">Current Password</div>' +
                            '<div><input type="password" id="curPassword" class="text-input" /></div>' +
                            '<div class="label-input">New Password</div>' +
                            '<div><input type="password" id="newPassword" class="text-input" /></div>' +
                            '<div class="label-input">Confirm New Password</div>' +
                            '<div><input type="password" id="confPassword" class="text-input" />' +
                            '<input type="button" value="Change Password" id="chngPwdButton" />' +
                            '<input type="button" value="Cancel" id="cancelButton" />' +
                            '<div id="signinHelpLink">Sign in help</div>' +
                            '<div id="errorMsg"></div>' +
                            '<div id="forgotPwdLink">Forgot Password</div>' +
                            '</div>');
    $('#chngPwdButton',this.chngPwdUI).bind('click', function() {userVE.validateChngPwdUI();});
    $('#cancelButton',this.chngPwdUI).bind('click', function() {userVE.loadUserInfoUI();});
    $('#signinHelpLink',this.chngPwdUI).bind('click', function() {userVE.showSignInHelp();});
    $('#forgotPwdLink',this.chngPwdUI).bind('click', function() {userVE.loadforgotPwdUI();});
    //display UI
    $('#curPassword',this.chngPwdUI).html("");
    $('#newPassword',this.chngPwdUI).html("");
    $('#confPassword',this.chngPwdUI).html("");
    $('#errorMsg',this.chngPwdUI).html("");
    this.userDiv.html("");
    this.userDiv.append(this.chngPwdUI);
    DEBUG.traceExit("userVE.loadChangePasswordUI"," user is "+this.username);
  },


/**
* put your comment there...
*
*/

validateChngPwdUI: function(){
  DEBUG.traceEntry("userVE.validateChngPwdUI"," user editor");
  var $userPassword = $('#curPassword',this.chngPwdUI),
      userpwd = $userPassword.val(),
      $newPassword = $('#newPassword',this.chngPwdUI),
      newpwd = $newPassword.val(),
      $confPassword = $('#confPassword',this.chngPwdUI),
      confpwd = $confPassword.val(),
      $errorMsg = $('#errorMsg',this.chngPwdUI);
  if (!userpwd || userpwd.length <7 || userpwd.length > 20) {
    $errorMsg.html("invalid Current Password!");
    $userPassword.focus();
  } else if (!newpwd || newpwd.length <7 || newpwd.length > 20) {
    $errorMsg.html("Password is required and must be from 7 to 20 charaters!");
    $newPassword.focus();
  } else if (!confpwd || newpwd != confpwd) {
    $errorMsg.html("New Password must match confirm Password!");
    $confPassword.focus();
  }else{
    $errorMsg.html("");
    this.chngPwd(userpwd,newpwd);
  }
  DEBUG.traceExit("userVE.validateChngPwdUI"," user editor");
},


/**
* put your comment there...
*
*/

  loadUserPreferences: function(){
    var userVE = this;
    DEBUG.traceEntry("userVE.loadUserPreferences"," user editor");
//    DEBUG.log("gen""in login user editor username= " + username + " password= " + password + (stayLoggedIn?" with persistent session":""));
    //call login service
    $.ajax({
        dataType: 'json',
        url: basepath+'/services/getUserPreferences.php?db='+dbName,
        asynch: true,
        success: function (data, status, xhr) {
                   if (data && data.error) {
                     $('#errorMsg',userVE.signInUI).html(data.error);
                   } else {
                     userVE.userPreference = data;
                     setTimeout(function () {userVE.loadUserInfoUI();
                                             userVE.updateEditInfo();},50);
                   }
                 },// end success cb
        error: function (xhr,status,error) {
                 // show login error msg
                 $('#errorMsg',userVE.signInUI).html("Invalid Username or Password!");
               }
    });// end ajax
    DEBUG.traceExit("userVE.loadUserPreferences"," user editor");
  },


/**
* put your comment there...
*
* @param edID
* @param visIDs
* @param attrIDs
* @param persist
*/

  saveUserPreferences: function(edID,visIDs,attrIDs,persist){
    var userVE = this,
        userPrefsData = {};
    DEBUG.traceEntry("userVE.saveUserPreferences"," user editor");
    if (edID) {
      userPrefsData['editUserID'] = edID;
    }
    if (visIDs) {
      userPrefsData['defVisIDs'] = visIDs;
    }
    if (attrIDs) {
      userPrefsData['defAttrIDs'] = attrIDs;
    }
    if (persist) {
      userPrefsData['persist'] = persist;
    }
    $.ajax({
        dataType: 'json',
        url: basepath+'/services/saveUserPreferences.php?db='+dbName,
        asynch: true,
        data: userPrefsData,
        success: function (data, status, xhr) {
                   if (data && data.error) {
                     $('#errorMsg',userVE.signInUI).html(data.error);
                   } else {
                     userVE.userPreference = data;
                     setTimeout(function () {userVE.loadUserInfoUI();
                                             userVE.updateEditInfo();},50);
                   }
                 },// end success cb
        error: function (xhr,status,error) {
                 // show login error msg
                 $('#errorMsg',userVE.signInUI).html("Invalid Username or Password!");
               }
    });// end ajax
    DEBUG.traceExit("userVE.saveUserPreferences"," user editor");
  },


/**
* put your comment there...
*
*/

  loadUserInfoUI: function(){
    var userVE = this;
    DEBUG.traceEntry("userVE.loadUserInfoUI");
    if (typeof this.userPreference != 'object' || Object.keys(this.userPreference).length == 0) {
      this.loadUserPreferences();
      return;
    }
    if (this.userInfoUI) {
      delete this.userInfoUI;
    }
    this.userInfoUI = $('<div id="userInfo">' +
                      '<div class="label-form">'+(this.username?this.username:'Unknown')+'</div>' +
                      '<div id="logoutLink">Logout</div>' +
                      '<div id="logChangesLink">Change Log</div>'+
                      '<div id="chgPasswordLink">Change password</div>' +
                      '<div id="userPreferencesDiv">User Preferences</div>' +
                      '<div id="workspaceLabel">Workspaces</div>' +
                      '<div id="errorMsg"></div>' +
                      '<a id="logChangesButton" visibility= "hidden" target="_blank" href="'+basepath+'/services/logChanges.php?db='+dbName+'"/>' +
                      '</div>');
    $('#logoutLink',this.userInfoUI).unbind('click').bind('click', function() {userVE.logout();});
    $('#logChangesLink',this.userInfoUI).unbind('click').bind('click', function() {
      $('#logChangesButton',userVE.userInfoUI).get(0).click();
    });
    $('#chgPasswordLink',this.userInfoUI).unbind('click').bind('click', function() {userVE.loadChangePasswordUI();});
    this.prefUIDiv = $('#userPreferencesDiv',this.userInfoUI);
    this.prefUIDiv.unbind('click').bind('click', function() {
                                  if (!$(this).hasClass('expanded')) {
                                    $(this).addClass('expanded');
                                    userVE.prefsExpanded = true;
                                  } else {
                                    // reset preferences UI
                                    // remove class to hide UI
                                    $(this).removeClass('expanded')
                                    delete userVE.prefsExpanded;
                                    if (userVE.editorUI.hasClass("edit")) {
                                      userVE.editorUI.removeClass("edit");
                                    }
                                    if (userVE.defVisUI.hasClass("edit")) {
                                      userVE.defVisUI.removeClass("edit");
                                    }
                                    if (userVE.defAttrUI.hasClass("edit")) {
                                      userVE.defAttrUI.removeClass("edit");
                                    }
                                  }
                                });
    this.createEditorUI();
    this.createVisibilityUI();
    this.createDefAttrUI();
    if (this.prefsExpanded) {
      this.prefUIDiv.addClass('expanded');
    }
    //display UI
    $('#errorMsg',this.userInfoUI).html("");
    this.userDiv.html("");
    this.userDiv.append(this.userInfoUI);
    DEBUG.traceExit("userVE.loadUserInfoUI");
  },


/**
* put your comment there...
*
*/

  createEditorUI: function() {
    var userVE = this,
        value = this.userPreference.userDefPrefs.defaultEditUserID ? this.userPreference.userDefPrefs.defaultEditUserID:"";
    var source =
            {
                localdata: this.userPreference.userUIList,
                datatype: "array"
            };
    var dataAdapter = new $.jqx.dataAdapter(source);
    DEBUG.traceEntry("userVE.createEditorUI");
    //create UI container
    this.editorUI = $('<div class="editorUI"></div>');
    this.prefUIDiv.append(this.editorUI);
    //create label
    this.editorUI.append($('<div class="propDisplayUI">'+
                          '<div class="valueLabelDiv propDisplayElement">'+
                          '<span class="valueLabelDivHeader">Edit as: </span>'+
                          '<span class="valueLabelDivEditor"></span>'+
                          '</div>'+
                          '</div>'));
    //create input with save button
    this.editorUI.append($('<div class="propEditUI">'+
                    '<div class="uGroupListDiv propEditElement"></div>'+
                    '</div>'));
    //attach event handlers
      //click to edit
      this.editorUI.unbind("click").bind("click",function(e) {
        if (userVE.editorUI.hasClass("edit")) {
          userVE.editorUI.removeClass("edit");
        } else {
          userVE.editorUI.addClass("edit");
        }
        e.stopImmediatePropagation();
        return false;
      });
      //blur to cancel
      this.editorUI.unbind("blur").bind("blur",function(e) {
          userVE.editorUI.removeClass("edit");
      });
      this.uGroupList = $('div.uGroupListDiv',this.editorUI);
      // Create jqxListBox
      this.uGroupList.jqxListBox({  source: dataAdapter,
                                  displayMember: "name",
                                  valueMember: "id",
                                  height: 350,
                                  width: 200,
          renderer: function (index, label, value) {
              var datarecord = userVE.userPreference.userUIList[index];
              return'<span title="'+datarecord.description + '" tag="'+datarecord.id+'" >' + datarecord.name + '</span>';
          }
      });
      //select cur Editor in list and make it visible
      var curEditor;
      if (value) {
        curEditor = this.uGroupList.jqxListBox('getItemByValue', value);
        if (curEditor) {
          this.uGroupList.jqxListBox('selectItem', curEditor);
          this.uGroupList.jqxListBox('ensureVisible', curEditor);
          $('.valueLabelDivEditor',userVE.editorUI).html(curEditor.label);
        }
      }

      //handle editor select
      this.uGroupList.unbind("select").bind("select",function(e) {
        var args = e.args, item = args.item, editorName = item.label, editorID = item.value;
        $('.valueLabelDivEditor',userVE.editorUI).html(editorName);
        userVE.editorUI.removeClass("edit");
        userVE.saveUserPreferences(editorID,null,null,1);
        //if editor has changed then save tempory session preferences
      });
    DEBUG.traceExit("userVE.createEditorUI");
  },


/**
* put your comment there...
*
*/

  getEditAsEditorName: function() {
    var editorID, editorName = this.username, editAsEditor;
    if (this.userPreference && this.userPreference.userDefPrefs &&
         this.userPreference.userDefPrefs.defaultEditUserID && this.uGroupList) {
     editorID = this.userPreference.userDefPrefs.defaultEditUserID;
     editAsEditorItem = this.uGroupList.jqxListBox('getItemByValue', editorID);
     if (editAsEditorItem && editAsEditorItem.label) {
       editorName = editAsEditorItem.label;
     }
    }
    return editorName;
  },


/**
* put your comment there...
*
*/

  createVisibilityUI: function() {
    var userVE = this,
        visIDs = this.userPreference.userDefPrefs.defaultVisibilityIDs ? this.userPreference.userDefPrefs.defaultVisibilityIDs:null;
    var source =
            {
                localdata: this.userPreference.userUIList,
                datatype: "array"
            };
    var dataAdapter = new $.jqx.dataAdapter(source);
    DEBUG.traceEntry("userVE.createVisibilityUI");
    //create UI container
    this.defVisUI = $('<div class="defVisUI"></div>');
    this.prefUIDiv.append(this.defVisUI);
    //create label
    this.defVisUI.append($('<div class="propDisplayUI">'+
                          '<div class="valueLabelDiv propDisplayElement">'+
                          '<span class="valueLabelDivHeader">Visibility: </span>'+
                          '<span class="valueLabelDivVis"></span>'+
                          '</div>'+
                          '</div>'));
    //create input with save button
    this.defVisUI.append($('<div class="propEditUI">'+
                    '<div class="uGroupMultiListDiv propEditElement"></div>'+
                    '</div>'));
    //attach event handlers
      //click to edit
      this.defVisUI.unbind("click").bind("click",function(e) {
        if (userVE.defVisUI.hasClass("edit")) {
          userVE.defVisUI.removeClass("edit");
        } else {
          userVE.defVisUI.addClass("edit");
        }
        e.stopImmediatePropagation();
        return false;
      });
      //blur to cancel
      this.defVisUI.unbind("blur").bind("blur",function(e) {
        if (!$(e.originalEvent.explicitOriginalTarget).hasClass('saveDiv')) {//all but save button
          userVE.defVisUI.removeClass("edit");
        }
      });
      this.uGroupMultiList = $('div.uGroupMultiListDiv',this.defVisUI);
      // Create jqxListBox
      this.uGroupMultiList.jqxListBox({  source: dataAdapter,
                                  displayMember: "name",
                                  valueMember: "id",
                                  checkboxes: true,
                     //             itemHeight: 70,
                                  height: 350,
                                  width: 200,
          renderer: function (index, label, value) {
              var datarecord = userVE.userPreference.userUIList[index];
              return'<span title="'+datarecord.description + '" tag="'+datarecord.id+'" >' + datarecord.name + '</span>';
          }
      });
      //check cur Visibility entries
      var ugrIDVisible, visItem, i, visLabel = "";
      if (visIDs.length > 0) {
        for (i in visIDs) {
          ugrIDVisible = visIDs[i];
          visItem = this.uGroupMultiList.jqxListBox('getItemByValue', ugrIDVisible);
          this.uGroupMultiList.jqxListBox('checkItem', visItem);
          if (visLabel && visItem && visItem.label) {
            visLabel += ", "+visItem.label;
          } else if (visItem && visItem.label){
            visLabel = visItem.label;
          }
        }
        $('.valueLabelDivVis',userVE.defVisUI).html(visLabel);
      }

      //handle editor select
      this.uGroupMultiList.unbind("checkChange").bind("checkChange",function(e) {
        var args = e.args,
            item = args.item,
            visItem, i, visLabel = "",visIDs = [];
            checkedItems = userVE.uGroupMultiList.jqxListBox('getCheckedItems');
        if (checkedItems.length > 0) {
          for (i in checkedItems) {
            visItem = checkedItems[i];
            if (visLabel) {
              visLabel += ", "+visItem.label;
            } else {
              visLabel = visItem.label;
            }
            visIDs.push(visItem.value);
          }
        }
        //if visibility has changed then save tempory session preferences
        $('.valueLabelDivVis',userVE.defVisUI).html(visLabel);
        userVE.defVisUI.removeClass("edit");
        userVE.saveUserPreferences(null,visIDs,null,1);
      });
    DEBUG.traceExit("userVE.createVisibilityUI");
  },


/**
* put your comment there...
*
* @returns {String}
*/

  getVisibilityLabel: function() {
    var visIDs=[], ugrIDVisible, visItem, i, visLabel = "";
    if (this.userPreference && this.userPreference.userDefPrefs &&
         this.userPreference.userDefPrefs.defaultVisibilityIDs && this.uGroupMultiList) {
      visIDs = this.userPreference.userDefPrefs.defaultVisibilityIDs;
    }
    if (visIDs.length > 0) {
      for (i in visIDs) {
        ugrIDVisible = visIDs[i];
        visItem = this.uGroupMultiList.jqxListBox('getItemByValue', ugrIDVisible);
        if (visLabel && visItem && visItem.label) {
          visLabel += ", "+visItem.label;
        } else if (visItem && visItem.label){
          visLabel = visItem.label;
        }
      }
    }
    return visLabel;
  },


/**
* put your comment there...
*
*/

  createDefAttrUI: function() {
    var userVE = this,
        value = this.userPreference.userDefPrefs.defaultAttributionIDs ? this.userPreference.userDefPrefs.defaultAttributionIDs[0]:"",
        source =
            {
                datatype: "jsonp",
                datafields: [
                    { name: 'label' },
                    { name: 'value' }
                ],
                url: basepath+"/services/searchAttributions.php?db="+dbName,
            };
            dataAdapter = new $.jqx.dataAdapter(source,
                {
                  formatData: function (data) {
                      srchString = userVE.attrSearchInput.val();
                      if (srchString) {
                        data.titleContains = userVE.attrSearchInput.val();
                      }
                      return data;
                  }
                }
            );
    DEBUG.traceEntry("userVE.createDefAttrUI");
    //create UI container
    this.defAttrUI = $('<div class="defAttrUI"></div>');
    this.prefUIDiv.append(this.defAttrUI);
    //create label
    this.defAttrUI.append($('<div class="propDisplayUI">'+
                          '<div class="valueLabelDiv propDisplayElement">'+
                          '<span class="valueLabelDivHeader">Attribute with: </span>'+
                          '<span class="valueLabelDivAttr"></span>'+
                          '</div>'+
                          '</div>'));
    //create input with save button
    this.defAttrUI.append($('<div class="propEditUI">'+
                          '<div>' +
                            '<span class="attrSearchLabel" >Search: </span>' +
                            '<input class="attrSearchInput" placeholder="Type name here" type="text"/>' +
                          '</div>' +
                          '<div class="attrListDiv propEditElement"></div>'+
                         '</div>'));
    //attach event handlers
      //click to edit
      this.defAttrUI.unbind("click").bind("click",function(e) {
        if (userVE.defAttrUI.hasClass("edit")) {
          //userVE.defAttrUI.removeClass("edit");
          if (!$(e.originalEvent.explicitOriginalTarget).hasClass('attrSearchInput')) {//all but save button
            userVE.defAttrUI.removeClass("edit");
          }
        } else {
          userVE.defAttrUI.addClass("edit");
        }
        e.stopImmediatePropagation();
        return false;
      });
      //blur to cancel
      this.defAttrUI.unbind("blur").bind("blur",function(e) {
        if (!$(e.originalEvent.explicitOriginalTarget).hasClass('attrSearchInput')) {//all but save button
          userVE.defAttrUI.removeClass("edit");
        }
      });
    this.attrList = $('div.attrListDiv',this.defAttrUI);
      this.attrSearchInput = $('.attrSearchInput',this.defAttrUI);
      // Create jqxListBox
      this.attrList.jqxListBox({  source: dataAdapter,
                                  displayMember: "name",
                                  valueMember: "id",
                                  height: 350,
                                  width: 200
      });

      //handle attr select
      this.attrList.unbind("select").bind("select",function(e) {
        var args = e.args, item = args.item, attrName = item.label, attrID = item.value;
        $('.valueLabelDivAttr',userVE.defAttrUI).html(attrName);
        userVE.defAttrUI.removeClass("edit");
        //ensure change against userPreference
        if (value != attrID){
          //if editor has changed then save tempory session preferences
          userVE.saveUserPreferences(null,null,[attrID],1);
        }
      });

      this.attrList.unbind('bindingComplete').bind('bindingComplete', function (event) {
        var curAttr;
        if (value) {
          curAttr = userVE.attrList.jqxListBox('getItemByValue', value);
          if (curAttr) {
            userVE.attrList.jqxListBox('selectItem', curAttr);
            userVE.attrList.jqxListBox('ensureVisible', curAttr);
            $('.valueLabelDivAttr',userVE.defAttrUI).html(curAttr.label);
          }
        }
      });

      this.attrSearchInput.on('keyup', function (e) {
          if (userVE.timer) clearTimeout(userVE.timer);
          userVE.timer = setTimeout(function () {
              dataAdapter.dataBind();
          }, 300);
      });
    DEBUG.traceExit("userVE.createDefAttrUI");
  },


/**
* put your comment there...
*
* @returns {String}
*/

  getAttributionLabel: function() {
    var attrIDs=[], attrID, attrItem, i, attrLabel = "";
    if (this.userPreference && this.userPreference.userDefPrefs &&
         this.userPreference.userDefPrefs.defaultAttributionIDs && this.attrList) {
      attrIDs = this.userPreference.userDefPrefs.defaultAttributionIDs;
    }
    if (attrIDs.length > 0) {
      for (i in attrIDs) {
        attrID = attrIDs[i];
        attrItem = this.attrList.jqxListBox('getItemByValue', attrID);
        if (attrLabel && attrItem && attrItem.label) {
          attrLabel += ", "+attrItem.label;
        } else if (attrItem && attrItem.label){
          attrLabel = attrItem.label;
        }
      }
    }
    return attrLabel;
  },


/**
* put your comment there...
*
*/

  updateEditInfo: function(){
    var editInfoHTML = "", editorName, visLabel, attribLabel, editInfoLabel;
    $('.editinfolabel',this.hdrDiv).remove();
    editorName = this.getEditAsEditorName();
    visLabel = this.getVisibilityLabel();
    attribLabel = this.getAttributionLabel();
    if (editorName && editorName != "Guest" || visLabel || attribLabel) {
        editInfoHTML = '<div class="editinfolabel">' +
              (editorName?'<span class="editinfoname">E: '+editorName+" </span>":"") +
              (visLabel?'<span class="editinfovis">V: '+visLabel+" </span>":"") +
              (attribLabel?'<span class="editinfoattr">A: '+attribLabel+"</span>":"") +
              '</div>';
      }
    if (editInfoHTML) {
      this.hdrDiv.append($(editInfoHTML));
    }
  },


/**
* put your comment there...
*
*/

  loadLoggedInUI: function(){
    var userVE = this,
        loggedInLink = $('<a class="loggedInLink">logout: '+this.username+'</a>');
    loggedInLink.bind('click',function() {
                                    userVE.logout();
//                                    userVE.loadUserInfoUI();
                                    });
    this.userDiv.html("");
      this.userDiv.append(loggedInLink);
  }
}

