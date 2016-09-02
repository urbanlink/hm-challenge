jQuery(document).ready(function($) {

  // Handle toggle challenge item checkbox toggle
  $('ol#challenge-list li input').click(function(e) {
    var self = this;

    var data = {
      'action': 'hmchallenge_toggleitem',
      'post_id': e.currentTarget.id,
      'status': e.currentTarget.checked
    };

    $.post(ajax_object.ajax_url, data, function(result) {
      if (result==='success') {

      } else {
        console.log(result);
        e.currentTarget.checked = !e.currentTarget.checked;
      }
    })
  });

  // Handle delete a challenge item
  $('ol#challenge-list li a.remove').click(function(e) {
    var self = this;
    e.preventDefault();
    e.stopPropagation();

    if (e.currentTarget.id) {
      var data = {
        'action': 'hmchallenge_removeitem',
        'item': e.currentTarget.id
      };
      jQuery.post(ajax_object.ajax_url, data, function(result) {
        if (result === 'success') {
          $(self).parent().remove();
        } else {
          console.log(result);
        }
      });
    }
  });


  // Handle new challenge item form click
	$('#challenge-submit').click(function(e) {
    e.preventDefault();
    e.stopPropagation();

    var itemTitle = $("#challenge-item").val();

    if (itemTitle) {
      var data = {
    		'action': 'hmchallenge_additem',
    		'item': $("#challenge-item").val()
    	};
    	// We can also pass the url value separately from ajaxurl for front end AJAX implementations
    	jQuery.post(ajax_object.ajax_url, data, function(result) {
        console.log(result);
        if (result.status === 'success') {
          var item = '<li class="challenge-listitem "><input type="checkbox" id="' + result.item.id + '" checked=""></input>' + result.item.title + ' <a href class="remove" id="' + result.item.id + '"><i class="fa fa-trash remove"></i></a></li>';
          $(item).appendTo($("#challenge-list"));
          $('.empty').remove();
          $("#challenge-item").val('');
          $('.challenge-user-inactive').hide();
        } else {
          console.log(result);
        }
    	});
    }

  });
});
