/*
 * PECI - A simplified interface for non-superadmin LimeSurvey users.
 * Copyright (C) 2012 Haute École de Gestion de Genève
 * Javier Belmonte <javier.belmonte@hesge.ch>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

function openPeciPopup(action, parameters) {
	var url = 'popup_maker.php?action=' + action + '&' + parameters;

	$.modal('<iframe src="' + url + '" style="width: 830px; height: 430px; border: 0px;">', {
		closeHTML:"",
		containerCss: {
			backgroundColor: "white",
			border: "2px solid black",
			width: "830px",
			height: "430px",
			padding: "5px"
		},
		overlayCss: {
			backgroundColor: "gray",
		},
		onClose: function (dialog) {
//			if (confirm('All changes will be lost!')) {
//				dialog.data.fadeOut('slow', function () {
//					dialog.container.slideUp('slow', function () {
//						dialog.overlay.fadeOut('slow', function () {
							$.modal.close(); // must call this!
//						});
//					});
//				});
//			}
		},
		overlayClose:true
	});
}

function submitAsParent(info) {
//	alert("user.php" + JSON.stringify(info, undefined, 2));
	$.post("user.php", info, function(data) {
		if (info.action == "insertsurvey") {
			location.href = "user.php?sid=" + data;
		} else {
			location.href = "user.php?sid=" + info.sid;
		}
	});
}

/**
 * Takes a form name, builds the JSON data object needed to
 * submit the action and submits it from the parent.
 * @param form
 */
function submitFormAsParent(form) {
	var elements = form.elements;
	var jsonString = '';
	
	for (var i = 0; i < elements.length; i++) {
		if (elements[i].type != 'radio' && elements[i].type != 'button') {
			jsonString += '"' + elements[i].name + '": ' + '"' + elements[i].value + '", ';
		}
	}

	var jsonObject = eval('({' + jsonString + '})');
	
	parent.submitAsParent(jsonObject);
}
