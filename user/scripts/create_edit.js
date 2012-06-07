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

function openGroupPopup(action, parameters) {
	var url = 'questiongrouppopup.php?action=' + action + '&' + parameters;

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
			if (confirm('All changes will be lost!')) {
//				dialog.data.fadeOut('slow', function () {
//					dialog.container.slideUp('slow', function () {
//						dialog.overlay.fadeOut('slow', function () {
							$.modal.close(); // must call this!
//						});
//					});
//				});
			}
		},
		overlayClose:true
	});
}

function submitAsParent(data) {
	$.post("user.php", data, function() {location.reload();});
}