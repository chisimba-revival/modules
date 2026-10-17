/** Native question descriptions and navigation; preserves server-generated routes. */
function setDescriptionVisible(id, visible) {
    var node = document.getElementById(id);
    if (node) { node.hidden = !visible; node.style.display = visible ? "" : "none"; }
}
document.addEventListener("DOMContentLoaded", function() {
    var choice = document.getElementById("existingQ");
    if (!choice) return;
    choice.addEventListener("change", function() { goToPage(choice.value); });
    goToPage(choice.value);
});

var goToPage = function(page) {
    hideAll();
    if(page != '-') {
        switch(page) {
            case 'mcq':
                hideAll();
                setDescriptionVisible("simpleDesc", true);
                break;
            case 'calcQ':
                hideAll();
                setDescriptionVisible("calcQDesc", true);
                break;
            case 'matchQ':
                hideAll();
                setDescriptionVisible("matchQDesc", true);
                break;
            case 'numericalQ':
                hideAll();
                setDescriptionVisible("numericalQDesc", true);
                break;
            case 'shortansQ':
                hideAll();
                setDescriptionVisible("shortansQDesc", true);
                break;
            case 'category':
                hideAll();
                setDescriptionVisible("categoryDesc", true);
                break;
            case 'descriptionlist':
                hideAll();
                setDescriptionVisible("adddescriptionDesc", true);
                break;
            case 'showRSA':
                hideAll();
                setDescriptionVisible("RSA", true);
                break;
            case 'showSCQ':
                hideAll();
                setDescriptionVisible("SCQ", true);
                break;
            default:
                hideAll();
        }
    }
}

var hideAll = function() {
    setDescriptionVisible("calcQDesc", false);
    setDescriptionVisible("matchQDesc", false);
    setDescriptionVisible("numericalQDesc", false);
    setDescriptionVisible("shortansQDesc", false);
    setDescriptionVisible("categoryDesc", false);
    setDescriptionVisible("adddescriptionDesc", false);
    setDescriptionVisible("simpleDesc", false);
    setDescriptionVisible("RSA", false);
    setDescriptionVisible("SCQ", false);
}

var goAddQuestion = function() {
    var choice = document.getElementById("existingQ");
    if (!choice) return;
    var page = choice.value;
    if(page != '-') {
        switch(page) {
            case 'calcQ':
                showCalcQ();
                break;
            case 'matchQ':
                showMatchQ();
                break;
            case 'numericalQ':
                showNumericalQ();
                break;
            case 'shortansQ':
                showShortAnsQ();
                break;
            case 'category':
                showCategory();
                break;
            case 'descriptionlist':
                showDescription();
                break;
            case 'showRSA':
                showRSA();
                break;
            case 'showSCQ':
                showSCQ();
                break;
            default:
                showSimpleMCQ();
        }
    }
}
var showSCQ = function() {
    window.location.href = scqUrl;
}
var showCalcQ = function() {
    window.location.href = calqUrl;
}

var showMatchQ = function() {
    window.location.href = matchingqUrl;
}

var showDescription = function() {
   window.location.href = descriptionUrl;
}

var showCategory = function() {
    window.location.href = categoryUrl;
}

var showSimpleMCQ = function() {
    window.location.href = mcqUrl;
}

var showNumericalQ = function() {
    window.location.href = numericalqUrl;
}

var showShortAnsQ = function() {
    window.location.href = shortanswerqUrl;
}
var showRSA = function() {
    window.location.href = randomSAUrl;
}