<?php

function angeltypes_title() {
  return _("Angeltypes");
}

/**
 * Route angeltype actions.
 */
function angeltypes_controller() {
  if (! isset($_REQUEST['action']))
    $_REQUEST['action'] = 'list';
  switch ($_REQUEST['action']) {
    default:
    case 'list':
      list($title, $content) = angeltypes_list_controller();
      break;
    case 'view':
      list($title, $content) = angeltype_controller();
      break;
    case 'edit':
      list($title, $content) = angeltype_edit_controller();
      break;
    case 'delete':
      list($title, $content) = angeltype_delete_controller();
      break;
  }
  
  return array(
      $title,
      $content 
  );
}

function angeltype_delete_controller() {
  global $privileges, $user;
  
  if (! in_array('admin_angel_types', $privileges))
    redirect(page_link_to('angeltypes'));
  
  $angeltype = mAngelType($_REQUEST['angeltype_id']);
  if ($angeltype === false)
    engelsystem_error("Unable to load angeltype.");
  if ($angeltype == null)
    redirect(page_link_to('angeltypes'));
  
  if (isset($_REQUEST['confirmed'])) {
    $result = AngelType_delete($angeltype);
    if ($result === false)
      engelsystem_error("Unable to delete angeltype.");
    
    engelsystem_log("Deleted angeltype: " . $name);
    success(sprintf(_("Angeltype %s deleted."), $name));
    redirect(page_link_to('angeltypes'));
  }
  
  return array(
      sprintf(_("Delete angeltype %s"), $angeltype['name']),
      AngelType_delete_view($angeltype) 
  );
}

function angeltype_edit_controller() {
  global $privileges, $user;
  
  if (! in_array('admin_angel_types', $privileges))
    redirect(page_link_to('angeltypes'));
  
  $name = "";
  $restricted = false;
  if (isset($_REQUEST['angeltype_id'])) {
    $angeltype = mAngelType($_REQUEST['angeltype_id']);
    if ($angeltype === false)
      engelsystem_error("Unable to load angeltype.");
    if ($angeltype == null)
      redirect(page_link_to('angeltypes'));
    
    $name = $angeltype['name'];
    $restricted = $angeltype['restricted'];
  }
  
  if (isset($_REQUEST['submit'])) {
    $ok = true;
    
    if (isset($_REQUEST['name'])) {
      list($valid, $name) = AngelType_validate_name($_REQUEST['name'], $angeltype);
      if (! $valid) {
        $ok = false;
        error(_("Please check the name. Maybe it already exists."));
      }
    }
    
    $restricted = isset($_REQUEST['restricted']);
    
    if ($ok) {
      $restricted = $restricted ? 1 : 0;
      if (isset($angeltype)) {
        $result = AngelType_update($angeltype['id'], $name, $restricted);
        if ($result === false)
          engelsystem_error("Unable to update angeltype.");
        engelsystem_log("Updated angeltype: " . $name . ", restricted: " . $restricted);
        $angeltype_id = $angeltype['id'];
      } else {
        $angeltype_id = AngelType_create($name, $restricted);
        if ($angeltype_id === false)
          engelsystem_error("Unable to create angeltype.");
        engelsystem_log("Created angeltype: " . $name . ", restricted: " . $restricted);
      }
      
      success("Angel type saved.");
      redirect(page_link_to('angeltypes') . '&action=view&angeltype_id=' . $angeltype_id);
    }
  }
  
  return array(
      isset($angeltype) ? sprintf(_("Edit %s"), $name) : _("Add new angeltype"),
      AngelType_edit_view($name, $restricted) 
  );
}

/**
 * View details of a given angeltype.
 */
function angeltype_controller() {
  global $privileges, $user;
  
  if (! isset($_REQUEST['angeltype_id']))
    redirect(page_link_to('angeltypes'));
  
  $angeltype = mAngelType($_REQUEST['angeltype_id']);
  if ($angeltype === false)
    engelsystem_error("Unable to load angeltype.");
  if ($angeltype == null)
    redirect(page_link_to('angeltypes'));
  
  $user_angeltype = UserAngelType_by_User_and_AngelType($user, $angeltype);
  if ($user_angeltype === false)
    engelsystem_error("Unable to load user angeltype.");
  
  $members = Users_by_angeltype($angeltype);
  if ($members === false)
    engelsystem_error("Unable to load members.");
  
  return array(
      sprintf(_("Team %s"), $angeltype['name']),
      AngelType_view($angeltype, $members, $user_angeltype, in_array('admin_user_angeltypes', $privileges), in_array('admin_angel_types', $privileges)) 
  );
}

/**
 * View a list of all angeltypes.
 */
function angeltypes_list_controller() {
  global $privileges, $user;
  
  $angeltypes = AngelTypes_with_user($user);
  if ($angeltypes === false)
    engelsystem_error("Unable to load angeltypes.");
  
  foreach ($angeltypes as &$angeltype) {
    $actions = array(
        '<a class="view" href="' . page_link_to('angeltypes') . '&action=view&angeltype_id=' . $angeltype['id'] . '">' . _("view") . '</a>' 
    );
    
    if (in_array('admin_angel_types', $privileges)) {
      $actions[] = '<a class="edit" href="' . page_link_to('angeltypes') . '&action=edit&angeltype_id=' . $angeltype['id'] . '">' . _("edit") . '</a>';
      $actions[] = '<a class="delete" href="' . page_link_to('angeltypes') . '&action=delete&angeltype_id=' . $angeltype['id'] . '">' . _("delete") . '</a>';
    }
    
    $angeltype['membership'] = "";
    if ($angeltype['user_angeltype_id'] != null) {
      if ($angeltype['restricted']) {
        if ($angeltype['confirm_user_id'] == null)
          $angeltype['membership'] = '<img src="pic/icons/lock.png" alt="' . _("Unconfirmed") . '" title="' . _("Unconfirmed") . '"> ' . _("Unconfirmed");
        else
          $angeltype['membership'] = '<img src="pic/icons/tick.png" alt="' . _("Member") . '" title="' . _("Member") . '"> ' . _("Member");
      } else
        $angeltype['membership'] = '<img src="pic/icons/tick.png" alt="' . _("Member") . '" title="' . _("Member") . '"> ' . _("Member");
      $actions[] = '<a class="cancel" href="' . page_link_to('user_angeltypes') . '&action=delete&user_angeltype_id=' . $angeltype['user_angeltype_id'] . '">' . _("leave") . '</a>';
    } else {
      $angeltype['membership'] = '<img src="pic/icons/cross.png" alt="" title="">';
      $actions[] = '<a class="add" href="' . page_link_to('user_angeltypes') . '&action=add&angeltype_id=' . $angeltype['id'] . '">' . _("join") . '</a>';
    }
    
    $angeltype['restricted'] = $angeltype['restricted'] ? '<img src="pic/icons/lock.png" alt="' . _("Restricted") . '" title="' . _("Restricted") . '">' : '';
    $angeltype['name'] = '<a href="' . page_link_to('angeltypes') . '&action=view&angeltype_id=' . $angeltype['id'] . '">' . $angeltype['name'] . '</a>';
    
    $angeltype['actions'] = join(" ", $actions);
  }
  
  return array(
      angeltypes_title(),
      AngelTypes_list_view($angeltypes, in_array('admin_angel_types', $privileges)) 
  );
}
?>