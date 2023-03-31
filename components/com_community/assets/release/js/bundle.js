(function () {
/**
 * @license almond 0.2.9 Copyright (c) 2011-2014, The Dojo Foundation All Rights Reserved.
 * Available via the MIT or new BSD license.
 * see: http://github.com/jrburke/almond for details
 */
//Going sloppy to avoid 'use strict' string cost, but strict practices should
//be followed.
/*jslint sloppy: true */
/*global setTimeout: false */

var requirejs, require, define;
(function (undef) {
    var main, req, makeMap, handlers,
        defined = {},
        waiting = {},
        config = {},
        defining = {},
        hasOwn = Object.prototype.hasOwnProperty,
        aps = [].slice,
        jsSuffixRegExp = /\.js$/;

    function hasProp(obj, prop) {
        return hasOwn.call(obj, prop);
    }

    /**
     * Given a relative module name, like ./something, normalize it to
     * a real name that can be mapped to a path.
     * @param {String} name the relative name
     * @param {String} baseName a real name that the name arg is relative
     * to.
     * @returns {String} normalized name
     */
    function normalize(name, baseName) {
        var nameParts, nameSegment, mapValue, foundMap, lastIndex,
            foundI, foundStarMap, starI, i, j, part,
            baseParts = baseName && baseName.split("/"),
            map = config.map,
            starMap = (map && map['*']) || {};

        //Adjust any relative paths.
        if (name && name.charAt(0) === ".") {
            //If have a base name, try to normalize against it,
            //otherwise, assume it is a top-level require that will
            //be relative to baseUrl in the end.
            if (baseName) {
                //Convert baseName to array, and lop off the last part,
                //so that . matches that "directory" and not name of the baseName's
                //module. For instance, baseName of "one/two/three", maps to
                //"one/two/three.js", but we want the directory, "one/two" for
                //this normalization.
                baseParts = baseParts.slice(0, baseParts.length - 1);
                name = name.split('/');
                lastIndex = name.length - 1;

                // Node .js allowance:
                if (config.nodeIdCompat && jsSuffixRegExp.test(name[lastIndex])) {
                    name[lastIndex] = name[lastIndex].replace(jsSuffixRegExp, '');
                }

                name = baseParts.concat(name);

                //start trimDots
                for (i = 0; i < name.length; i += 1) {
                    part = name[i];
                    if (part === ".") {
                        name.splice(i, 1);
                        i -= 1;
                    } else if (part === "..") {
                        if (i === 1 && (name[2] === '..' || name[0] === '..')) {
                            //End of the line. Keep at least one non-dot
                            //path segment at the front so it can be mapped
                            //correctly to disk. Otherwise, there is likely
                            //no path mapping for a path starting with '..'.
                            //This can still fail, but catches the most reasonable
                            //uses of ..
                            break;
                        } else if (i > 0) {
                            name.splice(i - 1, 2);
                            i -= 2;
                        }
                    }
                }
                //end trimDots

                name = name.join("/");
            } else if (name.indexOf('./') === 0) {
                // No baseName, so this is ID is resolved relative
                // to baseUrl, pull off the leading dot.
                name = name.substring(2);
            }
        }

        //Apply map config if available.
        if ((baseParts || starMap) && map) {
            nameParts = name.split('/');

            for (i = nameParts.length; i > 0; i -= 1) {
                nameSegment = nameParts.slice(0, i).join("/");

                if (baseParts) {
                    //Find the longest baseName segment match in the config.
                    //So, do joins on the biggest to smallest lengths of baseParts.
                    for (j = baseParts.length; j > 0; j -= 1) {
                        mapValue = map[baseParts.slice(0, j).join('/')];

                        //baseName segment has  config, find if it has one for
                        //this name.
                        if (mapValue) {
                            mapValue = mapValue[nameSegment];
                            if (mapValue) {
                                //Match, update name to the new value.
                                foundMap = mapValue;
                                foundI = i;
                                break;
                            }
                        }
                    }
                }

                if (foundMap) {
                    break;
                }

                //Check for a star map match, but just hold on to it,
                //if there is a shorter segment match later in a matching
                //config, then favor over this star map.
                if (!foundStarMap && starMap && starMap[nameSegment]) {
                    foundStarMap = starMap[nameSegment];
                    starI = i;
                }
            }

            if (!foundMap && foundStarMap) {
                foundMap = foundStarMap;
                foundI = starI;
            }

            if (foundMap) {
                nameParts.splice(0, foundI, foundMap);
                name = nameParts.join('/');
            }
        }

        return name;
    }

    function makeRequire(relName, forceSync) {
        return function () {
            //A version of a require function that passes a moduleName
            //value for items that may need to
            //look up paths relative to the moduleName
            return req.apply(undef, aps.call(arguments, 0).concat([relName, forceSync]));
        };
    }

    function makeNormalize(relName) {
        return function (name) {
            return normalize(name, relName);
        };
    }

    function makeLoad(depName) {
        return function (value) {
            defined[depName] = value;
        };
    }

    function callDep(name) {
        if (hasProp(waiting, name)) {
            var args = waiting[name];
            delete waiting[name];
            defining[name] = true;
            main.apply(undef, args);
        }

        if (!hasProp(defined, name) && !hasProp(defining, name)) {
            throw new Error('No ' + name);
        }
        return defined[name];
    }

    //Turns a plugin!resource to [plugin, resource]
    //with the plugin being undefined if the name
    //did not have a plugin prefix.
    function splitPrefix(name) {
        var prefix,
            index = name ? name.indexOf('!') : -1;
        if (index > -1) {
            prefix = name.substring(0, index);
            name = name.substring(index + 1, name.length);
        }
        return [prefix, name];
    }

    /**
     * Makes a name map, normalizing the name, and using a plugin
     * for normalization if necessary. Grabs a ref to plugin
     * too, as an optimization.
     */
    makeMap = function (name, relName) {
        var plugin,
            parts = splitPrefix(name),
            prefix = parts[0];

        name = parts[1];

        if (prefix) {
            prefix = normalize(prefix, relName);
            plugin = callDep(prefix);
        }

        //Normalize according
        if (prefix) {
            if (plugin && plugin.normalize) {
                name = plugin.normalize(name, makeNormalize(relName));
            } else {
                name = normalize(name, relName);
            }
        } else {
            name = normalize(name, relName);
            parts = splitPrefix(name);
            prefix = parts[0];
            name = parts[1];
            if (prefix) {
                plugin = callDep(prefix);
            }
        }

        //Using ridiculous property names for space reasons
        return {
            f: prefix ? prefix + '!' + name : name, //fullName
            n: name,
            pr: prefix,
            p: plugin
        };
    };

    function makeConfig(name) {
        return function () {
            return (config && config.config && config.config[name]) || {};
        };
    }

    handlers = {
        require: function (name) {
            return makeRequire(name);
        },
        exports: function (name) {
            var e = defined[name];
            if (typeof e !== 'undefined') {
                return e;
            } else {
                return (defined[name] = {});
            }
        },
        module: function (name) {
            return {
                id: name,
                uri: '',
                exports: defined[name],
                config: makeConfig(name)
            };
        }
    };

    main = function (name, deps, callback, relName) {
        var cjsModule, depName, ret, map, i,
            args = [],
            callbackType = typeof callback,
            usingExports;

        //Use name if no relName
        relName = relName || name;

        //Call the callback to define the module, if necessary.
        if (callbackType === 'undefined' || callbackType === 'function') {
            //Pull out the defined dependencies and pass the ordered
            //values to the callback.
            //Default to [require, exports, module] if no deps
            deps = !deps.length && callback.length ? ['require', 'exports', 'module'] : deps;
            for (i = 0; i < deps.length; i += 1) {
                map = makeMap(deps[i], relName);
                depName = map.f;

                //Fast path CommonJS standard dependencies.
                if (depName === "require") {
                    args[i] = handlers.require(name);
                } else if (depName === "exports") {
                    //CommonJS module spec 1.1
                    args[i] = handlers.exports(name);
                    usingExports = true;
                } else if (depName === "module") {
                    //CommonJS module spec 1.1
                    cjsModule = args[i] = handlers.module(name);
                } else if (hasProp(defined, depName) ||
                           hasProp(waiting, depName) ||
                           hasProp(defining, depName)) {
                    args[i] = callDep(depName);
                } else if (map.p) {
                    map.p.load(map.n, makeRequire(relName, true), makeLoad(depName), {});
                    args[i] = defined[depName];
                } else {
                    throw new Error(name + ' missing ' + depName);
                }
            }

            ret = callback ? callback.apply(defined[name], args) : undefined;

            if (name) {
                //If setting exports via "module" is in play,
                //favor that over return value and exports. After that,
                //favor a non-undefined return value over exports use.
                if (cjsModule && cjsModule.exports !== undef &&
                        cjsModule.exports !== defined[name]) {
                    defined[name] = cjsModule.exports;
                } else if (ret !== undef || !usingExports) {
                    //Use the return value from the function.
                    defined[name] = ret;
                }
            }
        } else if (name) {
            //May just be an object definition for the module. Only
            //worry about defining if have a module name.
            defined[name] = callback;
        }
    };

    requirejs = require = req = function (deps, callback, relName, forceSync, alt) {
        if (typeof deps === "string") {
            if (handlers[deps]) {
                //callback in this case is really relName
                return handlers[deps](callback);
            }
            //Just return the module wanted. In this scenario, the
            //deps arg is the module name, and second arg (if passed)
            //is just the relName.
            //Normalize module name, if it contains . or ..
            return callDep(makeMap(deps, callback).f);
        } else if (!deps.splice) {
            //deps is a config object, not an array.
            config = deps;
            if (config.deps) {
                req(config.deps, config.callback);
            }
            if (!callback) {
                return;
            }

            if (callback.splice) {
                //callback is an array, which means it is a dependency list.
                //Adjust args if there are dependencies
                deps = callback;
                callback = relName;
                relName = null;
            } else {
                deps = undef;
            }
        }

        //Support require(['a'])
        callback = callback || function () {};

        //If relName is a function, it is an errback handler,
        //so remove it.
        if (typeof relName === 'function') {
            relName = forceSync;
            forceSync = alt;
        }

        //Simulate async callback;
        if (forceSync) {
            main(undef, deps, callback, relName);
        } else {
            //Using a non-zero value because of concern for what old browsers
            //do, and latest browsers "upgrade" to 4 if lower value is used:
            //http://www.whatwg.org/specs/web-apps/current-work/multipage/timers.html#dom-windowtimers-settimeout:
            //If want a value immediately, use require('id') instead -- something
            //that works in almond on the global level, but not guaranteed and
            //unlikely to work in other AMD implementations.
            setTimeout(function () {
                main(undef, deps, callback, relName);
            }, 4);
        }

        return req;
    };

    /**
     * Just drops the config on the floor, but returns req in case
     * the config return value is used.
     */
    req.config = function (cfg) {
        return req(cfg);
    };

    /**
     * Expose module registry for debugging and tooling
     */
    requirejs._defined = defined;

    define = function (name, deps, callback) {

        //This module may not have dependencies
        if (!deps.splice) {
            //deps is not an array, so probably means
            //an object literal or factory function for
            //the value. Adjust args.
            callback = deps;
            deps = [];
        }

        if (!hasProp(defined, name) && !hasProp(waiting, name)) {
            waiting[name] = [name, deps, callback];
        }
    };

    define.amd = {
        jQuery: true
    };
}());

define("../../vendors/almond", function(){});

;(function( root ) {

/**
 * Relative path for assets loading.
 * @name joms.BASE_URL
 * @const {string}
 */
joms.BASE_URL = root.joms_base_url;
delete root.joms_base_url;

// Fix www/non-www redirection.
var reDomain = /https?:\/\/[^/]+/;
var baseDomain = joms.BASE_URL.match( reDomain );
var realDomain = (location.href).match( reDomain );
if ( baseDomain && realDomain && baseDomain[0] !== realDomain[0] ) {
    joms.BASE_URL.replace( reDomain, realDomain[0] );
}

/**
 * Relative path for assets loading.
 * @name joms.ASSETS_URL
 * @const {string}
 */
joms.ASSETS_URL = root.joms_assets_url;
delete root.joms_assets_url;

/**
 * Detect mobile browser.
 * @name joms.mobile
 * @const {boolean}
 */
joms.mobile = (function() {
    var mobile = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i;
    return mobile.test( navigator.userAgent );
})();

/**
 * Detect mobile safari (iOS) browser.
 * @name joms.ios
 * @const {boolean}
 */
joms.ios = (function() {
    var ios = /iphone|ipad|ipod/i;
    return ios.test( navigator.userAgent );
})();

/**
 * Detect screen size based on it's width and css breakpoint rule.
 * Breakpoints: 0 - 480 | 481 - 991 | 992 - ~
 * @function joms.screenSize
 */
joms.screenSize = function() {
    var width = window.innerWidth;
    if ( width <= 480 ) return 'small';
    if ( width <= 991 ) return 'medium';
    return 'large';
};

/**
 * jQuery.ajax wrapper to perform ajax request
 * @function joms.ajax
 * @param {object} options - Ajax call options
 */
joms.ajax = function( options ) {
    var url   = root.jax_live_site || '',
        token = root.jax_token_var || '_no_token_found_',
        data  = {};

    options || (options = {});

    // Match jax.call parameters.
    data[token]  = 1;
    data.task    = 'azrul_ajax';
    data.option  = options.option || 'community';
    data.func    = options.func;
    data.no_html = 1;

    delete options.option;
    delete options.func;

    // Build arguments.
    if ( options.data && options.data.length ) {
        for ( var i = 0, arg; i < options.data.length; i++ ) {
            arg = options.data[ i ];
            if ( typeof arg === 'string' )
                arg = arg.replace( /"/g, '&quot;' );
            if ( !joms._.isArray( arg ) )
                arg = [ '_d_', encodeURIComponent( arg ) ];
            data[ 'arg' + ( i + 2 ) ] = JSON.stringify( arg );
        }
    }

    var response;

    // Override options.
    options.url      = url;
    options.type     = 'post';
    options.dataType = 'json';
    options.data     = data;

    options.success = function( json ) {
        if ( json ) response = json;
    };

    options.complete = function() {
        response || (response = { error: 'Undefined error.' });

        if ( response.noLogin ) {
            joms.api && joms.api.login( response );
            joms.view.misc.fixSVG();
            return;
        }

        // Execute additional callbacks (if any).
        var stop;
        if ( joms._onAjaxReponseQueue &&
             joms._onAjaxReponseQueue[ data.func ] &&
             joms._onAjaxReponseQueue[ data.func ].length ) {
            for ( var i = 0; i < joms._onAjaxReponseQueue[ data.func ].length; i++ )  {
                if ( typeof joms._onAjaxReponseQueue[ data.func ][i] === 'function' ) {
                    if ( joms._onAjaxReponseQueue[ data.func ][i]( response ) === false ) {
                        stop = true;
                    }
                }
            }
        }

        if ( typeof options.callback === 'function' && ( !stop ) ) {
            options.callback( response );
        }

        joms.view.misc.fixSVG();
    };

    // Perform ajax request.
    return joms.jQuery.ajax( options );
};

/**
 * Hide non-jomsocial contents.
 * @function joms.____
 */
joms.____ = function() {
    var node = joms.jQuery('#community-wrap');
    while ( node.length && node[0].tagName.toLowerCase() !== 'body' ) {
        node.siblings().hide();
        node = node.parent();
        node.css({
            border: '0 none',
            padding: 0,
            marginTop: 0,
            marginBottom: 0,
            width: 'auto'
        });
    }
};

/**
 * Prints SVG icons into document.body for testing purpose.
 * @function joms._printSVGIcons
 */
joms._printSVGIcons = function() {
    var icons = [ 'home', 'newspaper', 'pencil', 'image', 'images', 'camera', 'play', 'film', 'camera2', 'bullhorn', 'library', 'profile', 'support', 'envelope', 'location', 'clock', 'bell', 'calendar', 'box-add', 'box-remove', 'bubble', 'bubbles', 'user', 'users', 'spinner', 'search', 'key', 'lock', 'wrench', 'cog', 'gift', 'remove', 'briefcase', 'switch', 'signup', 'list', 'menu', 'earth', 'link', 'eye', 'star', 'star2', 'star3', 'thumbs-up', 'happy', 'smiley', 'tongue', 'sad', 'wink', 'grin', 'cool', 'angry', 'evil', 'shocked', 'confused', 'neutral', 'wondering', 'warning', 'info', 'blocked', 'spam', 'close', 'checkmark', 'plus', 'arrow-right', 'arrow-left', 'tab', 'filter', 'console', 'share', 'facebook', 'libreoffice', 'file-zip', 'arrow-down', 'redo', 'tag', 'search-user' ];
    var $ct = joms.jQuery('<div>');

    for ( var i = 0; i < icons.length; i++ ) {
        $ct.append(
            '<svg viewBox="0 0 30 30" class="joms-icon" style="width:30px;height:30px">' +
            '<use xlink:href="#joms-icon-' + icons[i] +  '"></use>' +
            '</svg>'
        );
    }

    $ct.appendTo( document.body );
};

// AMD-style output.
if ( typeof define === 'function' ) define('core',[],function () {
	return root.joms;
});

})( this );

(function( root, $, IS_DESKTOP, factory ) {

    joms.util || (joms.util = {});
    joms.util.crop = factory( root, $, IS_DESKTOP );

})( window, joms.jQuery, !joms.mobile, function( window, $, IS_DESKTOP ) {

var cropper, wrapper, hammertime, measurements, resizeDirection;

function Cropper( elem ) {
    return Cropper.attach( elem );
}

Cropper.init = function() {
    wrapper || ( wrapper = $('<div class="joms-cropper__wrapper" />') );
    cropper || ( cropper = $('<div class="joms-cropper__box" />') );
};

Cropper.attach = function( elem ) {
    Cropper.init();
    Cropper.detach();
    reset();

    $( elem ).wrap( wrapper );

    // todo (rudy) change this hack
    wrapper = $( elem ).parent();

    cropper.insertAfter( elem );

    if ( !hammertime ) {
        hammertime = new joms.Hammer( cropper[0] );
        hammertime.on( 'touch drag release', function( e ) {
            e.stopPropagation();
            e.preventDefault();
            e.gesture.stopPropagation();
            e.gesture.preventDefault();

            if ( e.type === 'touch' ) {
                disableDesktopEvents();
                onTouch( e.gesture );
            } else if ( e.type !== 'release' ) {
                onDragOrResize( e.gesture );
            } else {
                onRelease( e.gesture );
                enableDesktopEvents();
            }
        });
    }

    enableDesktopEvents();

    return elem;
};

Cropper.detach = function() {
    Cropper.init();
    cropper.detach();
    wrapper.children().unwrap();
    wrapper.detach();
};

Cropper.getSelection = function() {
    var mea = measurements;

    return {
        x: mea.cropperLeft,
        y: mea.cropperTop,
        width: mea.cropperWidth,
        height: mea.cropperHeight
    };
};

function reset() {
    cropper.css({
        top: '',
        left: '',
        right: '',
        bottom: '',
        width: '',
        height: '',
        webkitTransform: '',
        mozTransform: '',
        transform: ''
    });
}

function measure() {
    var wrp = wrapper[0],
        img = wrapper.children('img'),
        pos = cropper.position();

    measurements = {
        imageWidth     : img.width(),
        imageHeight    : img.height(),
        wrapperTop     : wrp.scrollTop,
        wrapperLeft    : wrp.scrollLeft,
        wrapperWidth   : wrapper.width(),
        wrapperHeight  : wrapper.height(),
        cropperTop     : pos.top + wrp.scrollTop,
        cropperLeft    : pos.left + wrp.scrollLeft,
        cropperWidth   : cropper.outerWidth(),
        cropperHeight  : cropper.outerHeight()
    };
}

function onTouch( gesture ) {
    measure();
    resizeDirection = getResizeDirection( gesture );
}

var onDragOrResize = joms._.throttle(function( gesture ) {
    resizeDirection ? onResize( gesture ) : onDrag( gesture );
}, IS_DESKTOP ? 10 : 100 );

function onDrag( gesture ) {
    var mea = measurements,
        top = gesture.deltaY,
        left = gesture.deltaX,
        value;

    // Respect horizontal boundaries.
    left = Math.min( left, mea.imageWidth - mea.cropperWidth - mea.cropperLeft );
    left = Math.max( left, 0 - mea.cropperLeft );

    // Respect vertical boundaries.
    top = Math.min( top, mea.imageHeight - mea.cropperHeight - mea.cropperTop );
    top = Math.max( top, 0 - mea.cropperTop );

    value = 'translate3d(' + left + 'px, ' + top + 'px, 0)';

    cropper.css({
        webkitTransform: value,
        mozTransform: value,
        transform: value
    });
}

function onResize( gesture ) {
    var dir = resizeDirection,
        mea = measurements,
        css = {};

    if ( dir.match( /n/ ) ) {
        css.top    = 'auto';
        css.bottom = mea.wrapperHeight - mea.cropperTop - mea.cropperHeight;
        css.height = mea.cropperHeight - gesture.deltaY;
    } else if ( dir.match( /s/ ) ) {
        css.bottom = 'auto';
        css.top    = mea.cropperTop;
        css.height = mea.cropperHeight + gesture.deltaY;
    }

    if ( dir.match( /e/ ) ) {
        css.right = 'auto';
        css.left  = mea.cropperLeft;
        css.width = mea.cropperWidth + gesture.deltaX;
    } else if ( dir.match( /w/ ) ) {
        css.left  = 'auto';
        css.right = mea.wrapperWidth - mea.cropperLeft - mea.cropperWidth;
        css.width = mea.cropperWidth - gesture.deltaX;
    }

    // Restrict cropper box to 1:1 ratio.
    css.width = css.height = Math.max( css.width || 0, css.height || 0, 64 );

    // Respect vertical boundaries.
    if ( dir.match( /n/ ) ) {
        css.height = Math.min( css.height, mea.wrapperHeight - css.bottom );
    } else if ( dir.match( /s/ ) ) {
        css.height = Math.min( css.height, mea.imageHeight - css.top );
    } else if ( cropper[0].style.top !== 'auto' ) {
        css.height = Math.min( css.height, mea.imageHeight - parseInt( cropper.css('top') ) );
    } else {
        css.height = Math.min( css.height, mea.wrapperHeight - parseInt( cropper.css('bottom') ) );
    }

    // Respect horizontal boundaries.
    if ( dir.match( /e/ ) ) {
        css.width = Math.min( css.width, mea.imageWidth - css.left );
    } else if ( dir.match( /w/ ) ) {
        css.width = Math.min( css.width, mea.wrapperWidth - css.right );
    } else if ( cropper[0].style.left !== 'auto' ) {
        css.width = Math.min( css.width, mea.imageWidth - parseInt( cropper.css('left') ) );
    } else {
        css.width = Math.min( css.width, mea.wrapperWidth - parseInt( cropper.css('right') ) );
    }

    // Restrict cropper box to 1:1 ratio.
    css.width = css.height = Math.min( css.width, css.height );

    cropper.css( css );
}

function onRelease() {
    var pos = cropper.position(),
        mea = measurements;

    cropper.css({
        top: Math.max( pos.top + mea.wrapperTop, 0 ),
        left: Math.max( pos.left + mea.wrapperLeft, 0 ),
        right: '',
        bottom: '',
        webkitTransform: '',
        mozTransform: '',
        transform: ''
    });

    measure();
}

function getPointerPosition( pageX, pageY ) {
    var offset = cropper.offset();

    return {
        top  : pageY - offset.top,
        left : pageX - offset.left
    };
}

function getResizeDirection( gesture ) {
    var treshhold = IS_DESKTOP ? 15 : 20,
        pos = getPointerPosition( gesture.center.pageX, gesture.center.pageY ),
        mea = measurements,
        dir = '';

    if ( pos.top < treshhold ) {
        dir += 'n';
    } else if ( pos.top > mea.cropperHeight - treshhold ) {
        dir += 's';
    }

    if ( pos.left < treshhold ) {
        dir += 'w';
    } else if ( pos.left > mea.cropperWidth - treshhold ) {
        dir += 'e';
    }

    return dir;
}

function enableDesktopEvents() {
    if ( IS_DESKTOP ) {
        cropper.on( 'mousemove.joms-cropper', onMouseMove );
    }
}

function disableDesktopEvents() {
    if ( IS_DESKTOP ) {
        cropper.off( 'mousemove.joms-cropper' );
    }
}

function onMouseMove( e ) {
    var parentOffset = $( e.target ).parent().offset(),
        relX = e.pageX - parentOffset.left,
        relY = e.pageY - parentOffset.top,
        treshhold = 15,
        cursor = '',
        m;

    measure();
    m = measurements;

    if ( relY < m.cropperTop - m.wrapperTop + treshhold ) cursor += 'n';
    else if ( relY > m.cropperTop - m.wrapperTop + m.cropperHeight - treshhold ) cursor += 's';

    if ( relX < m.cropperLeft - m.wrapperLeft + treshhold ) cursor += 'w';
    else if ( relX > m.cropperLeft - m.wrapperLeft + m.cropperWidth - treshhold ) cursor += 'e';

    cropper.css({ cursor: cursor ? cursor + '-resize' : '' });
}

return Cropper;

});

define("utils/crop", function(){});

define('utils/map',[ 'core' ], function() {

var API_KEY = window.joms_gmap_key,
    $ = jQuery,
    instance;

function GoogleMaps() {
    if ( ! instance ) {
        instance = this;
    }
}

GoogleMaps.prototype = {

    search: function( keyword ) {
        var that = this;

        return $.Deferred(function( defer ) {
            that.autocompleteService().then(function( service ) {
                service.getPlacePredictions({ input: keyword }, function( results, status ) {
                    if ( status === 'OK' ) {
                        defer.resolve( that.mapResult( results ) );
                    } else {
                        defer.reject( status );
                    }
                });
            });
        });
    },

    mapResult: function( results ) {
        var data = [];

        if ( _.isArray( results ) ) {
            _.each( results, function( item ) {
                var id = item.place_id,
                    nameParts = item.description.split( /,\s(.+)?/ ),
                    name = nameParts[0],
                    description = nameParts[1] || '';

                data.push({ id: id, name: name, description: description });
            });
        }

        return data;
    },

    render: function( elem, place ) {
        var $elem = $( elem ).show(),
            map = $elem.data( 'joms-map' ),
            marker = $elem.data( 'joms-map-marker' ),
            name = place.formatted_address || '',
            location, viewport;

        if ( place.geometry ) {
            location = place.geometry.location;
            viewport = place.geometry.viewport;
        } else {
            location = new google.maps.LatLng( place.latitude, place.longitude );
        }

        if ( ! map ) {
            $elem.data( 'joms-map', map = new google.maps.Map( elem, {
                center: location,
                zoom: 15,
                draggable: false,
                scrollwheel: false,
                disableDefaultUI: true
            }) );
        }

        if ( ! marker ) {
            $elem.data( 'joms-map-marker', marker = new google.maps.Marker({
                map: map,
                position: location,
                title: name
            }) );
        }

        map.setCenter( location );
        marker.setPosition( location );
        if ( viewport ) {
            map.fitBounds( viewport );
        } else {
            map.setZoom( 15 );
        }
    },

    loadAPI: function() {
        var that = this;

        return $.Deferred(function( defer ) {
            var script, callback;

            if ( window.google && window.google.maps && window.google.maps.places ) {
                defer.resolve();
                return;
            }

            if ( that.loaded ) {
                defer.resolve();
                return;
            }

            that.queue = that.queue || [];
            that.queue.push( defer );

            if ( that.loading ) {
                return;
            }

            that.loading = true;

            callback = _.uniqueId( 'jomsCallback' );

            script = document.createElement( 'script' );
            script.type = 'text/javascript';
            script.src = 'https://maps.googleapis.com/maps/api/js?libraries=places' +
                ( API_KEY ? ( '&key=' + API_KEY ) : '' ) + '&callback=' + callback;

            window[ callback ] = function() {
                that.loaded = true;
                that.loading = false;
                while ( that.queue.length ) {
                    ( that.queue.shift() ).resolve();
                }
                delete window[ callback ];
            };

            document.body.appendChild( script );
        });
    },

    autocompleteService: function() {
        var that = this;

        return $.Deferred(function( defer ) {
            that.loadAPI().then(function() {
                var cache = '_cacheAutocompleteService';

                that[ cache ] = that[ cache ] || new google.maps.places.AutocompleteService();
                defer.resolve( that[ cache ] );
            });
        });
    },

    placeService: function() {
        var that = this;

        return $.Deferred(function( defer ) {
            that.loadAPI().then(function() {
                var cache = '_cachePlaceService',
                    div = document.createElement( 'div' );

                document.body.appendChild( div );
                that[ cache ] = that[ cache ] || new google.maps.places.PlacesService( div );
                defer.resolve( that[ cache ] );
            });
        });
    },

    placeDetail: function( id ) {
        var that = this;

        return $.Deferred(function( defer ) {
            that.placeService().then(function( service ) {
                var cache = '_cachePlaceDetail';

                that[ cache ] = that[ cache ] || {};

                if ( that[ cache ][ id ] ) {
                    defer.resolve( that[ cache ][ id ] );
                } else {
                    service.getDetails({ placeId: id }, function( place, status ) {
                        if ( status === 'OK' ) {
                            that[ cache ][ id ] = place;
                            defer.resolve( place );
                        } else {
                            defer.reject( status );
                        }
                    });
                }
            });
        });
    }

};

// Export as `joms.util.map`.
joms.util || (joms.util = {});
joms.util.map = function( callback ) {
    var gmap;

    if ( typeof callback !== 'function' ) {
        return;
    }

    gmap = new GoogleMaps();
    gmap.loadAPI().done(function() {
        callback();
    });
};

joms.util.map.search = function( keyword ) {
    return $.Deferred(function( defer ) {
        var gmap = new GoogleMaps();
        gmap.search( keyword ).done(function( results ) {
            defer.resolve( results );
        });
    });
};

joms.util.map.detail = function( id ) {
    return $.Deferred(function( defer ) {
        var gmap = new GoogleMaps();
        gmap.placeDetail( id ).done(function( place ) {
            defer.resolve( place );
        });
    });
};

});

define('utils/popup',[ 'core', 'utils/map' ], function() {

function Popup() {}

Popup.prototype.prepare = function( callback ) {
    var mfp, that;

    if ( joms.jQuery.magnificPopup ) {
        mfp = this.showPopup();
        callback( mfp );
        return;
    }

    that = this;
    this.loadlib(function() {
        if ( joms.jQuery.magnificPopup ) {
            mfp = that.showPopup();
            callback( mfp );
        }
    });
};

Popup.prototype.showPopup = function() {
    joms.jQuery.magnificPopup.open({
        type: 'inline',
        items: { src: [] },
        tClose: window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON,
        tLoading: window.joms_lang.COM_COMMUNITY_POPUP_LOADING
    });

    var mfp = joms.jQuery.magnificPopup.instance,
        className = 'joms-popup__wrapper';

    if ( joms.mobile ) {
        className += ' joms-popup__mobile';
    }

    mfp.container.addClass( className );
    mfp.updateStatus('loading');

    mfp.container
        .off('click.joms-closepopup', '.joms-js--button-close')
        .on('click.joms-closepopup', '.joms-js--button-close', function() {
            mfp.close();
        });

    return mfp;
};

Popup.prototype.loadlib = function( callback ) {
    callback();
};

// Factory.
joms.util || (joms.util = {});
joms.util.popup = new Popup();

});

(function( root, $, factory ) {

    joms.util || (joms.util = {});
    joms.util.dropdown = factory( root, $ );

    define('utils/dropdown',[ 'utils/popup' ], function() {
        return joms.util.dropdown;
    });

})( window, joms.jQuery, function( window, $, undefined ) {

var

// Event list.
evtClick = 'click.dropdown',
evtHide = 'collapse.dropdown',

// Selectors.
slrButton = '[data-ui-object=joms-dropdown-button]',
slrDropdown = '.joms-dropdown,.joms-popover',

// Element cache.
lastbtn,
lastdd,
popup,
elem,
doc;

function hide() {
    lastdd && lastdd.hide();
    lastbtn && btnRemoveClass( lastbtn );
}

function toggle( e ) {
    var btn, dd;

    e.stopPropagation();
    e.preventDefault();

    btn = $( e.currentTarget );
    dd = btn.siblings( slrDropdown );

    if ( !dd.length ) {
        return;
    }

    if ( dd.is(':visible') ) {
        dd.hide();
        btnRemoveClass( btn );
        return;
    }

    if ( joms.screenSize() === 'large' ) {
        hide();
        dd.show();
        btnAddClass( btn );
        lastbtn = btn;
        lastdd = dd;
        executeAdditionalFn( dd );
        return;
    }

    joms.util.popup.prepare(function( mfp ) {
        popup = mfp;
        popup.items[0] = {
            type: 'inline',
            src: buildHtml( dd )
        };

        popup.updateItemHTML();
        executeAdditionalFn( dd );

        elem = popup.contentContainer;
        elem.on( 'click', 'li > a', function() {
            popup.close();
        });
    });
}

function btnAddClass( btn ) {
    var par = btn.parent();
    if ( par.hasClass('.joms-focus__button--options--desktop') ) {
        par.addClass('active');
    } else {
        btn.addClass('active');
    }
}

function btnRemoveClass( btn ) {
    var par = btn.parent();
    if ( par.hasClass('.joms-focus__button--options--desktop') ) {
        par.removeClass('active');
    } else {
        btn.removeClass('active');
    }
}

function buildHtml( dd ) {
    return '<div class="joms-popup joms-popup--dropdown">' + dd[0].outerHTML + '</div>';
}

function executeAdditionalFn( dd ) {
    var className = dd.attr('class') || '',
        offset;

    // fix popup goes out of browser's window
    dd.css('left', '');
    offset = dd.offset();
    if ( offset.left < 0 ) {
        dd.css('left', -25 );
    }

    if ( className.match('joms-popover--toolbar-general') ) {
        joms.api.notificationGeneral();
        return;
    }
    if ( className.match('joms-popover--toolbar-friendrequest') ) {
        joms.api.notificationFriend();
        return;
    }
    if ( className.match('joms-popover--toolbar-pm') ) {
        joms.api.notificationPm();
        return;
    }
}

// Change privacy dropdown.
var selectPrivacy = joms._.debounce(function( e ) {
    var className = e.currentTarget.className || '',
        ul, li, btn, hidden, span, svg;

    if ( className.indexOf('joms-dropdown--privacy') < 0 ) {
        return;
    }

    ul  = $( e.currentTarget );
    li  = $( e.target ).closest('li');

    if ( li.length ) {
        btn    = $('.joms-button--privacy').filter('[data-name="' + ul.data('name') + '"]');
        hidden = btn.children('[data-ui-object=joms-dropdown-value]');
        span   = btn.children('span');
        svg    = btn.find('use');

        hidden.val( li.data('value') );
        span.html( li.children('span').html() );
        svg.attr( 'xlink:href', window.location.href.replace(/#.*$/, '') + '#' + li.data('classname') );
    }

    hide();
    popup && popup.close();

}, 100 );

function initialize() {
    uninitialize();

    doc || (doc = $( document.body ));
    doc.on( evtClick, hide );
    doc.on( evtClick, slrButton, toggle );
    doc.on( evtHide, slrButton, hide );
    doc.on( evtClick, slrDropdown, function( e ) {
        var shouldPropagate = $( e.target ).data( 'propagate' ) || $( e.currentTarget ).data( 'propagate' );
        if ( ! shouldPropagate ) {
            e.stopPropagation();
        }
        selectPrivacy( e );
    });
}

function uninitialize() {
    if ( doc ) {
        doc.off( evtClick );
        doc.off( evtClick, slrButton );
        doc.off( evtHide, slrButton );
        doc.off( evtClick, slrDropdown );
    }
}

// Exports.
return {
    start: initialize,
    stop: uninitialize
};

});

(function( root, $, factory ) {

    joms.util || (joms.util = {});
    joms.util.hovercard = factory( root, $ );

})( window, joms.jQuery, function( window, $ ) {

var card, showTimer, hideTimer, cache = {};

var MOUSEOVER_EVENT = 'mouseover.joms-hcard',
    MOUSEOUT_EVENT = 'mouseout.joms-hcard',
    IMG_SELECTOR = 'img[data-author]';

function initialize() {
    // Only enable on desktop browser.
    if ( joms.mobile ) {
        return;
    }

    // Attach handler.
    $( document.body )
        .off( MOUSEOVER_EVENT ).off( MOUSEOUT_EVENT )
        .on( MOUSEOVER_EVENT, IMG_SELECTOR, onMouseOver )
        .on( MOUSEOUT_EVENT, IMG_SELECTOR, onMouseOut );
}

function onMouseOver( e ) {
    var img = $( e.target ),
        id = img.data('author');

    // Remove title attribute to avoid redundancy.
    img.removeAttr('title');

    if ( !card ) {
        createCard();
    }

    clearTimeout( hideTimer );

    if ( cache[id] ) {
        clearTimeout( showTimer );
        showTimer = setTimeout(function() {
            updateCard( cache[id], img );
        }, 400 );
        return;
    }

    joms.ajax({
        func: 'profile,ajaxFetchCard',
        data: [ id ],
        callback: function( json ) {
            if ( json.html ) {
                cache[id] = json.html;
                clearTimeout( showTimer );
                updateCard( json.html, img );
            }
        }
    });
}

function onMouseOut() {
    clearTimeout( showTimer );
    hideTimer = setTimeout(function() {
        card && card.hide();
    }, 400 );
}

function createCard() {
    card = $('<div>Loading...</div>');
    card.css({ position: 'absolute', zIndex: 2000 });
    card.appendTo( document.body );

    card.on( MOUSEOVER_EVENT, function() { clearTimeout( hideTimer ); });
    card.on( MOUSEOUT_EVENT, onMouseOut );
}

function updateCard( html, img ) {
    var doc = $( document ),
        offset = img.offset(),
        width = img.width(),
        height = img.height(),
        maxWidth = window.innerWidth,
        maxHeight = window.innerHeight + doc.scrollTop(),
        cardHeight = 140,
        alignLeft = offset.left + 320 < maxWidth,
        top = offset.top + height + 10;

    card.html( html );
    card.css({
       top: -10000,
       left: alignLeft ? offset.left : '',
       right: alignLeft ? '' : maxWidth - offset.left - width
    });
    card.show();

    cardHeight = card.height();
    if ( top + cardHeight > maxHeight ) {
        top = offset.top - cardHeight - 4;
    }

    card.css({ top: top });
}

// Exports.
return {
    initialize: initialize
};

});

define("utils/hovercard", function(){});

(function( root, factory ) {

    joms.util || (joms.util = {});
    joms.util.loadLib = factory( root );

})( window, function( window ) {

// @todo: Google Map library loader.
function loadGmap( fn ) {
    return fn();
}

// MediaElement.js library loader.
function loadMediaElement( fn ) {
    if ( window.MediaElement ) {
        return fn();
    }

    joms.$LAB.script( joms.ASSETS_URL + 'vendors/mediaelement/mediaelement-and-player.min.js' ).wait(function () {
        fn();
    });
}

// Flowplayer library loader.
function loadFlowplayer( fn ) {
    if ( window.flowplayer ) {
        return fn();
    }

    joms.$LAB.script( joms.ASSETS_URL + 'flowplayer/flowplayer-3.2.6.min.js' ).wait(function () {
        fn();
    });
}

// Plupload library loader.
function loadPlupload( fn ) {
    if ( window.plupload ) {
        return fn();
    }

    joms.$LAB.script( joms.ASSETS_URL + 'vendors/plupload.min.js' ).wait(function() {
        fn();
    });
}

// Trumbowyg rich editor loader.
function loadTrumbowyg( fn ) {
    if ( !joms.jQuery ) {
        return false;
    }

    if ( joms.jQuery.trumbowyg ) {
        return fn();
    }

    joms.loadCSS( joms.ASSETS_URL + 'vendors/trumbowyg/ui/trumbowyg.min.css' );
    joms.$LAB.script( joms.ASSETS_URL + 'vendors/trumbowyg/trumbowyg.min.js' ).wait()
        .script( joms.ASSETS_URL + 'vendors/trumbowyg/plugins/base64/trumbowyg.base64.min.js' ).wait()
        .script( joms.ASSETS_URL + 'vendors/trumbowyg/plugins/upload/trumbowyg.upload.js' ).wait(function() {
            fn();
    });
}

// Load dragsort library.
function loadDragsort( fn ) {
    if ( window.Sortable ) {
        return fn();
    }

    joms.$LAB.script( joms.ASSETS_URL + 'dragsort/jquery.dragsort-0.5.1.min.js' ).wait(function() {
        fn();
    });
}

function load( lib, fn ) {
    if ( lib === 'gmap' ) {
        return loadGmap( fn );
    }

    if ( lib === 'mediaelement' ) {
        return loadMediaElement( fn );
    }

    if ( lib === 'flowplayer' ) {
        return loadFlowplayer( fn );
    }

    if ( lib === 'plupload' ) {
        return loadPlupload( fn );
    }

    if ( lib === 'trumbowyg' ) {
        return loadTrumbowyg( fn );
    }

    if ( lib === 'dragsort' ) {
        return loadDragsort( fn );
    }

    fn();
}

// Exports.
return load;

});

define("utils/loadlib", function(){});

(function( root, $, _, factory ) {

    var LocationAutocomplete = factory( root, $, _ );

    define('utils/location-autocomplete',[ 'utils/map' ], function() {
        return LocationAutocomplete;
    });

    // make jQuery plugin
    $.fn.locationAutocomplete = function() {
        return this.each(function() {
            var key = 'location-autocomplete',
                $el = $( this );
            if ( ! $el.data( key ) ) {
                $el.data( key, new LocationAutocomplete( this ));
            }
        });
    };

    // detect input on page load
    $(function() {
        $('.joms-input--location').locationAutocomplete();
    });

})( window, joms.jQuery, window._, function( window, $, _ ) {

function LocationAutocomplete( el ) {
    this.$input = $( el );

    this.$wrapper = this.$input.parent('.joms-location__wrapper');
    this.$inputDesc = this.$input.siblings('.js-desc');
    this.$inputLat = this.$input.siblings('.js-lat');
    this.$inputLng = this.$input.siblings('.js-lng');
    this.$description = this.$input.siblings('.joms-location__description');
    this.$dropdown = this.$input.siblings('.joms-location__dropdown');
    this.$loading = this.$dropdown.find('.joms-location__loading');
    this.$result = this.$dropdown.find('.joms-location__result');
    this.$header = this.$dropdown.find('.joms-location__header');
    this.$map = this.$dropdown.find('.joms-location__map');
    this.$list = this.$dropdown.find('.joms-location__list');

    this.$input.on('focus.joms-location', $.proxy( this.focus, this ));
    this.$input.on('blur.joms-location', $.proxy( this.hide, this ));
    this.$input.on('input.joms-location', $.proxy( this.search, this ));
    this.$dropdown.on('mousedown', '.joms-location__list a', $.proxy( this.select, this ));
    this.$dropdown.on('click', '.joms-location__close', $.proxy( this.hide, this ));
}

LocationAutocomplete.prototype.show = function() {
    this.$dropdown.show();
};

LocationAutocomplete.prototype.hide = function() {
    this.$dropdown && this.$dropdown.hide();
};

LocationAutocomplete.prototype.focus = function() {
    this._search_success && this.show();
};

LocationAutocomplete.prototype.search = function() {
    var query = $.trim( this.$input.val() );
    this.$wrapper.css('display', 'inline-block');
    this.$description.html( this.$description.data('tips') );
    if ( query ) {
        this.$result.hide();
        this.$loading.show();
        this.$dropdown.show();
        this._search( query );
    }
    setTimeout( $.proxy(function() {
        this.$wrapper.css('display', '');
    }, this ), 0 );
};

LocationAutocomplete.prototype._search = _.debounce(function( query ) {
    this._get_autocomplete_service().done(function( service ) {
        service.getPlacePredictions({ input: query }, $.proxy(function( results, status ) {
            this.$loading.hide();
            this.$result.show();
            if ( status === 'OK' ) {
                this._format_result( results ).done( $.proxy(function( html ) {
                    this.$list.html( html );
                    this._search_success = true;
                }, this ));
            }
        }, this ));
    });
}, 1000 );

LocationAutocomplete.prototype.select = function( e ) {
    var $item = $( e.currentTarget ),
        name = $item.find('.js-name').text(),
        desc = $item.find('.js-desc').text(),
        placeId = $item.data('place-id');

    e.preventDefault();
    e.stopPropagation();

    if ( placeId ) {
        this.$input.val( name );
        this.$inputDesc.val( desc );
        this.$description.html( desc );
        this._get_location_detail( placeId ).done(function( place ) {
            var loc = place.geometry.location;
            this.$inputLat.val( loc.lat() );
            this.$inputLng.val( loc.lng() );
            this._render_map( this.$map[0], place );
        });
    }
};

LocationAutocomplete.prototype._load_library = function() {
    return $.Deferred( $.proxy(function( defer ) {
        joms.util.map( $.proxy(function() {
            defer.resolveWith( this, [ window.google ]);
        }, this ));
    }, this ));
};

LocationAutocomplete.prototype._get_autocomplete_service = function() {
    return $.Deferred( $.proxy(function( defer ) {
        if ( this._autocomplete_service ) {
            defer.resolveWith( this, [ this._autocomplete_service ]);
        } else {
            this._load_library().done(function( google ) {
                this._autocomplete_service = new google.maps.places.AutocompleteService();
                defer.resolveWith( this, [ this._autocomplete_service ]);
            });
        }
    }, this ));
};

LocationAutocomplete.prototype._get_location_service = function() {
    return $.Deferred( $.proxy(function( defer ) {
        if ( this._location_service ) {
            defer.resolveWith( this, [ this._location_service ]);
        } else {
            this._load_library().done(function( google ) {
                var div = document.createElement('div');
                document.body.appendChild( div );
                this._location_service = new google.maps.places.PlacesService( div );
                defer.resolveWith( this, [ this._location_service ]);
            });
        }
    }, this ));
};

LocationAutocomplete.prototype._get_location_detail = function( id ) {
    return $.Deferred( $.proxy(function( defer ) {
        if ( this._location_cache && this._location_cache[ id ] ) {
            defer.resolveWith( this, [ this._location_cache[ id ] ]);
        } else {
            this._get_location_service().done(function( service ) {
                service.getDetails({ placeId: id }, $.proxy(function( place, status ) {
                    if ( status === 'OK' ) {
                        this._location_cache || (this._location_cache = {});
                        this._location_cache[ id ] = place;
                        defer.resolveWith( this, [ place ]);
                    } else {
                        defer.rejectWith( this, [ status ]);
                    }
                }, this ));
            });
        }
    }, this ));
};

LocationAutocomplete.prototype._render_map = function( div, place ) {
    var location = place.geometry.location,
        viewport = place.geometry.viewport,
        map, marker;

    div = $( div ).show();
    map = div.data('joms-map');
    marker = div.data('joms-map-marker');

    if ( !map ) {
        map = new window.google.maps.Map( div[0], {
            center: location,
            zoom: 15,
            draggable: false,
            scrollwheel: false,
            disableDefaultUI: true
        });
        div.data('joms-map', map );
    }

    if ( !marker ) {
        marker = new window.google.maps.Marker({
            position: location,
            map: map,
            title: 'You are here (more or less)'
        });
        div.data('joms-map-marker', marker );
    }

    map.setCenter( location );
    marker.setPosition( location );
    if ( viewport ) {
        map.fitBounds( viewport );
    } else {
        map.setZoom( 15 );
    }
};

LocationAutocomplete.prototype._format_result = function( results ) {
    return $.Deferred( $.proxy(function( defer ) {
        var html = [],
            defers = [],
            template;

        template = [
            '<a href="javascript:" data-place-id="{place_id}">',
            '<span class="joms-location__name js-name">{name}</span>',
            '<span class="joms-location__desc js-desc">{description}</span>',
            '</a>'
        ].join('');

        _.each( results, function( item ) {
            defers.push( this._get_location_detail( item.place_id ) );
        }, this );

        $.when.apply( $, defers ).then( $.proxy(function() {
            var html = _.map( arguments, function( place ) {
                return template
                    .replace('{place_id}', place.place_id )
                    .replace('{name}', place.name )
                    .replace('{description}', place.formatted_address || '&nbsp;' );
            });

            defer.resolveWith( this, [ html.join('') ]);
        }, this ));
    }, this ));
};

return LocationAutocomplete;

});

(function( root, factory ) {

    joms.util || (joms.util = {});
    joms.util.tab = factory( root );

})( window, function() {

function start() {
    startTab();
    startLegacyTab();
}

function startTab() {
    var cssTabBar  = '.joms-tab__bar',
        cssTabItem = '.joms-tab__content',
        doc;

    function toggle( e ) {
        var el = joms.jQuery( e.currentTarget ),
            par = el.parent( cssTabBar ),
            target = el.attr('href'),
            selected, url, clicked;

        if ( el.find('.joms-tab__bar--button').length ) {
            clicked = joms.jQuery( e.target );
            if ( clicked.hasClass('add') || clicked[0].tagName.match(/use|svg/i) ) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }

        if ( target.indexOf('#') !== 0 ) {
            return;
        }

        selected = el.closest( cssTabBar ).siblings( target );
        selected.show().siblings( cssTabItem ).hide();
        el.addClass('active').siblings('a').removeClass('active');

        url = par.data('id');
        if ( url ) {
            url = '#tab:' + url + '/' + el.data('id');
            window.location = url;
        }

        return false;
    }

    function initialize() {
        uninitialize();
        doc || (doc = joms.jQuery( document.body ));
        doc.on( 'click.joms-tab', cssTabBar + ' a', toggle );
    }

    function uninitialize() {
        doc && doc.off('click.joms-tab');
    }

    initialize();
}

function startLegacyTab() {
    joms.jQuery('.cTabsBar').on( 'click', 'li', function( e ) {
        var li = joms.jQuery( e.currentTarget ),
            wrapper = li.closest('.cTabsBar').siblings('.cTabsContentWrap'),
            index, tab;

        if ( !wrapper.length )
            return;

        index = li.prevAll().length;
        tab = wrapper.children('.cTabsContent').eq( index );

        if ( !tab.length )
            return;

        li.addClass('cTabCurrent').siblings('.cTabCurrent').removeClass('cTabCurrent');
        tab.siblings('.cTabsContent').hide();
        tab.show();
    });
}

// Exports.
return {
    start: start
};

});

define("utils/tab", function(){});

(function( root, $, factory ) {

    joms.util || (joms.util = {});
    joms.util.tagging = factory( root, $ );

    // Also register as jQuery plugin.
    $.fn.jomsTagging = function( extraFetch ) {
        return this.each(function() {
            joms.util.tagging( this, extraFetch );
        });
    };

})( window, joms.jQuery, function( window, $ ) {

var

// Virtual keys.
VK_ENTER   = 13,
VK_ESC     = 27,
VK_KEYUP   = 38,
VK_KEYDOWN = 40,

// Namespace.
namespace = 'joms-tagging',

// CSS selectors.
cssTextarea           = '.joms-textarea',
cssWrapper            = cssTextarea + '__wrapper',
cssBeautifier         = cssTextarea + '__beautifier',
cssHidden             = cssTextarea + '__hidden',
cssDropdown           = cssTextarea + '__tag-ct',
cssDropdownItem       = cssTextarea + '__tag-item',
cssDropdownItemActive = cssDropdownItem + '--active',

// Regular expressions.
rTags           = /@\[\[(\d+):contact:([^\]]+)\]\]/g,
rTag            = /@\[\[(\d+):contact:([^\]]+)\]\]/,
rHashTag        = /(^|#|\s)(#[^#\s]+)/g,
rHashTagReplace = '$1<b>$2</b>',
rEol            = /\n/g,
rEolReplace     ='<br>';

function Tagging( textarea, extraFetch ) {
    this.textarea = textarea;
    this.fetcher = extraFetch || false;
    this.$textarea = $( textarea );
    this.$textarea.data( 'initialValue', textarea.value );
    this.$textarea.data( namespace, this );
    this.$textarea.on( 'focus.' + namespace, $.proxy( this.initialize, this ) );

    return this;
}

Tagging.prototype.initialize = function() {
    var value, tags, match, start, i;

    this.dropdownIsOpened     = false;
    this.dropdownIsClicked    = false;
    this.dropdownSelectedItem = false;

    this.domPrepare();
    // this.inputPrepare();

    this.tagsAdded = [];
    value = '';

    if ( this.$textarea.data('initialValue') ) {
        value = this.textarea.value;
        tags = value.match( rTags );
        this.textarea.value = value.replace( rTags, '$2' );
        if ( tags && tags.length ) {
            for ( i = 0; i < tags.length; i++ ) {
                match = tags[i].match( rTag );
                start = value.indexOf( tags[i] );
                value = value.replace( tags[i], match[2] );
                this.tagsAdded.push({
                    id     : match[1],
                    name   : match[2],
                    start  : start,
                    length : match[2].length
                });
            }
        }
    }

    this.beautifierUpdate( value, this.tagsAdded );
    this.hiddenUpdate( value, this.tagsAdded );
    this.inputAutogrow();

    this.$textarea
        .off( 'focus.'   + namespace ).on( 'focus.'   + namespace, $.proxy( this.inputOnKeydown, this ) )
        .off( 'click.'   + namespace ).on( 'click.'   + namespace, $.proxy( this.inputOnKeydown, this ) )
        .off( 'keydown.' + namespace ).on( 'keydown.' + namespace, $.proxy( this.inputOnKeydown, this ) )
        .off( 'keyup.'   + namespace ).on( 'keyup.'   + namespace, $.proxy( this.inputOnKeyup, this ) )
        .off( 'input.'   + namespace ).on( 'input.'   + namespace, $.proxy( this.inputOnInput, this ) )
        .off( 'blur.'    + namespace ).on( 'blur.'    + namespace, $.proxy( this.inputOnBlur, this ) );

    this.$dropdown
        .off(  'mouseenter.' + namespace ).on( 'mouseenter.' + namespace, cssDropdownItem, $.proxy( this.dropdownOnMouseEnter, this ) )
        .off(  'mousedown.'  + namespace ).on( 'mousedown.'  + namespace, cssDropdownItem, $.proxy( this.dropdownOnMouseDown, this ) )
        .off(  'mouseup.'    + namespace ).on( 'mouseup.'    + namespace, cssDropdownItem, $.proxy( this.dropdownOnMouseUp, this ) );

    this.textarea.joms_beautifier = this.$beautifier;
    this.textarea.joms_hidden = this.$hidden;


    var that = this;
    this.textarea.joms_reset = function() {
        that.inputReset();
    };

};

Tagging.prototype.domPrepare = function() {
    this.$wrapper = this.$textarea.parent( cssWrapper );
    if ( !this.$wrapper.length ) {
        this.$textarea.wrap( '<div class="' + cssWrapper.substr(1) + '"></div>' );
        this.$wrapper = this.$textarea.parent();
    }

    this.$beautifier = this.$wrapper.children( cssBeautifier );
    if ( !this.$beautifier.length ) {
        this.$beautifier = $( '<div class="' + cssTextarea.substr(1) + ' ' + cssBeautifier.substr(1) + '"></div>' );
        this.$beautifier.prependTo( this.$wrapper );
    }

    this.$hidden = this.$wrapper.children( cssHidden );
    if ( !this.$hidden.length ) {
        this.$hidden = $( '<input type="hidden" class="' + cssHidden.substr(1) + '">' );
        this.$hidden.appendTo( this.$wrapper );
    }

    this.$dropdown = this.$wrapper.children( cssDropdown );
    if ( !this.$dropdown.length ) {
        this.$dropdown = $( '<div class="' + cssDropdown.substr(1) + '"></div>' );
        this.$dropdown.appendTo( this.$wrapper );
    }
};

Tagging.prototype.inputPrepare = function() {

};

// @todo
Tagging.prototype.inputReset = function() {
    if ( this.tagsAdded ) {
        this.tagsAdded = [];
    }

    // console.log('inputReset');
    // this.tagsAdded = [];
    // this.$hidden.val();
    // this.$textarea.val();
    // this.$beautifier.html( text );
    // this.$textarea.trigger( 'reset.' + namespace );
};

Tagging.prototype.inputAutogrow = function() {
    var prevHeight = +this.$textarea.data( namespace + '-prevHeight' ),
        height;

    this.$wrapper.css({ height: prevHeight });
    this.$textarea.css({ height: '' });

    height = this.textarea.scrollHeight + 2;
    this.$textarea.css({ height: height });
    if ( height !== +prevHeight ) {
        this.$textarea.data( namespace + '-prevHeight', height );
    }

    this.$wrapper.css({ height: '' });
};

Tagging.prototype.inputOnKeydown = function( e ) {
    // Catch dropdown navigation buttons.
    if ( this.dropdownIsOpened ) {
        if ([ VK_ENTER, VK_ESC, VK_KEYUP, VK_KEYDOWN ].indexOf( e.keyCode ) >= 0 ) {
            return false;
        }
    }

    // Reset input to initial state if Esc button is pressed.
    if ( e.keyCode === VK_ESC ) {
        this.inputReset();
        return false;
    }

    this.prevSelStart = this.textarea.selectionStart;
    this.prevSelEnd = this.textarea.selectionEnd;
};

Tagging.prototype.inputOnKeyup = function( e ) {
    if ( this.dropdownIsOpened ) {
        if ( e.keyCode === VK_KEYUP || e.keyCode === VK_KEYDOWN ) {
            this.dropdownChangeItem( e.keyCode );
            return false;
        }

        if ( e.keyCode === VK_ENTER ) {
            this.dropdownSelectItem();
            return false;
        }

        if ( e.keyCode === VK_ESC ) {
            this.dropdownHide();
            return false;
        }
    }
};

Tagging.prototype.inputOnInput = function() {
    var value = this.textarea.value,
        delta, tag, length, name, tmp, index, rMatch, rReplace, shift, i, j;

    // Shift tags position.
    if ( this.tagsAdded ) {

        // if text is selected (selectionStart !== selectionEnd)
        if ( this.prevSelStart !== this.prevSelEnd ) {
            for ( i = 0; i < this.tagsAdded.length; i++ ) {
                tag = this.tagsAdded[i];
                length = tag.start + tag.length;
                if (
                    // Intersection.
                    ( this.prevSelStart > tag.start && this.prevSelStart < length ) ||
                    ( this.prevSelEnd > tag.start && this.prevSelEnd < length ) ||
                    // Enclose.
                    ( tag.start >= this.prevSelStart && length <= this.prevSelEnd )
                ) {
                    this.tagsAdded.splice( i--, 1 );
                }
            }
        }

        delta = this.textarea.selectionStart - this.prevSelStart - ( this.prevSelEnd - this.prevSelStart );

        for ( i = 0; i < this.tagsAdded.length; i++ ) {
            tag = this.tagsAdded[i];

            // Tag's start is in right of or exactly at cursor position.
            if ( tag.start >= this.prevSelStart ) {
                tag.start += delta;
            } else {
                length = tag.start + tag.length;

                // Tag's end is in left of cursor position.
                if ( length < this.prevSelStart ) {
                    // do nothing

                // Cursor position is inside a tag.
                } else if ( length > this.prevSelStart ) {
                    // Not backspace.
                    if ( delta > 0 ) {
                        this.tagsAdded.splice( i--, 1 );
                    // Backspace.
                    } else if ( delta < 0 ) {
                        name = value.substring( tag.start, this.prevSelStart + delta );
                        index = name.split(' ').length - 1;
                        name = tag.name.split(' ');
                        name.splice( index, 1 );
                        name = name.join(' ');

                        tmp = tag.name.split(' ');
                        tmp = tmp.slice( 0, index );
                        tmp = tmp.join(' ');

                        rMatch = new RegExp( '^([\\s\\S]{' + tag.start + '})([\\s\\S]{' + ( tag.length + delta ) + '})' );
                        rReplace = '$1' + name;
                        this.textarea.value = this.textarea.value.replace( rMatch, rReplace );
                        this.textarea.setSelectionRange(tag.start + tmp.length, tag.start + tmp.length);

                        value = this.textarea.value;
                        shift = tag.length - name.length;
                        tag.name = name;
                        tag.length = name.length;

                        for ( j = i + 1; j < this.tagsAdded.length; j++ ) {
                            this.tagsAdded[j].start -= shift;
                        }

                        if ( !name.length ) {
                            this.tagsAdded.splice( i--, 1 );
                        }

                        i = this.tagsAdded.length;

                    }

                // Tag's end is exactly at cursor position... and a backspace is pressed.
                } else if ( delta < 0 ) {
                    name = tag.name.split(' ');
                    name.pop();
                    name = name.join(' ');

                    rMatch = new RegExp( '^([\\s\\S]{' + tag.start + '})([\\s\\S]{' + ( tag.length + delta ) + '})' );
                    rReplace = '$1' + name;
                    this.textarea.value = this.textarea.value.replace( rMatch, rReplace );
                    this.textarea.setSelectionRange(tag.start + name.length, tag.start + name.length);

                    value = this.textarea.value;
                    shift = tag.length - name.length;
                    tag.name = name;
                    tag.length = name.length;

                    for ( j = i + 1; j < this.tagsAdded.length; j++ ) {
                        this.tagsAdded[j].start -= shift;
                    }

                    if ( !name.length ) {
                        this.tagsAdded.splice( i--, 1 );
                    }

                    i = this.tagsAdded.length;
                }
            }
        }
    }

    this.inputAutogrow();
    this.beautifierUpdate( value, this.tagsAdded || [] );
    this.hiddenUpdate( value, this.tagsAdded || [] );
    this.dropdownToggle();
};

Tagging.prototype.inputOnBlur = function() {
    this.dropdownIsClicked || this.dropdownHide();
};

Tagging.prototype.beautifierUpdate = joms._.debounce(function( value, tags ) {
    var rMatch, rReplace, start, tag, i;

    if ( tags.length ) {
        rMatch = '^';
        rReplace = '';
        start = 0;

        for ( i = 0; i < tags.length; i++ ) {
            tag = tags[i];
            rMatch += '([\\s\\S]{' + ( tag.start - start ) + '})([\\s\\S]{' + tag.length + '})';
            rReplace += '$' + ( i * 2 + 1 ) + '[b]' + tag.name + '[/b]';
            start = tag.start + tag.length;
        }

        rMatch = new RegExp( rMatch );
        value = value.replace( rMatch, rReplace );
    }

    value = value.replace( /</g, '&lt;' ).replace( />/g, '&gt;' );
    value = value.replace( /\[(\/?b)\]/g, '<$1>' );
    value = value.replace( rHashTag, rHashTagReplace );
    value = value.replace( rEol, rEolReplace );

    this.$beautifier.html( value );

}, joms.mobile ? 100 : 1 );

Tagging.prototype.hiddenUpdate = joms._.debounce(function( value, tags ) {
    var rMatch, rReplace, start, tag, i;

    if ( tags.length ) {
        rMatch = '^';
        rReplace = '';
        start = 0;

        for ( i = 0; i < tags.length; i++ ) {
            tag = tags[i];
            rMatch += '([\\s\\S]{' + ( tag.start - start ) + '})([\\s\\S]{' + tag.length + '})';
            rReplace += '$' + ( i * 2 + 1 ) + '@[[' + tag.id + ':contact:' + tag.name + ']]';
            start = tag.start + tag.length;
        }

        rMatch = new RegExp( rMatch );
        value = value.replace( rMatch, rReplace );
    }

    this.$hidden.val( value );

}, joms.mobile ? 500 : 50 );

Tagging.prototype.dropdownToggle = joms._.debounce(function() {
    var cpos   = this.textarea.selectionStart,
        substr = this.textarea.value.substr( 0, cpos ),
        index  = substr.lastIndexOf('@');

    if ( index < 0 || ++index >= cpos ) {
        this.dropdownHide();
        return;
    }

    substr = substr.substring( index, cpos );

    this.dropdownFetch( substr, joms._.bind( this.dropdownUpdate, this ) );

}, joms.mobile ? 1000 : 200 );

Tagging.prototype.dropdownFetch = function( keyword, callback, friends ) {
    var source  = ( window.joms_friends || [] ).concat( friends || [] ),
        added   = this.tagsAdded || [],
        matches = [],
        uniques = [],
        item, name, isAdded, that, i, j;

    // Map data-source.
    if ( source && source.length ) {
        keyword = keyword.toLowerCase();
        for ( i = 0; (i < source.length) && (matches.length < 20); i++ ) {
            item = source[i];
            name = ( item.name || '' ).toLowerCase();
            if ( name.indexOf( keyword ) >= 0 ) {
                isAdded = false;
                for ( j = 0; j < added.length; j++ ) {
                    if ( +item.id === +added[j].id ) {
                        isAdded = true;
                        break;
                    }
                }

                if ( !isAdded && uniques.indexOf( +item.id ) < 0 ) {
                    uniques.push( +item.id );
                    matches.push({
                        id: item.id,
                        name: item.name,
                        img: item.avatar
                    });
                }
            }
        }
    }

    matches.sort(function( a, b ) {
        if ( a.name < b.name ) return -1;
        if ( a.name > b.name ) return 1;
        return 0;
    });

    callback( matches );

    if ( typeof this.fetcher === 'function' && !friends ) {
        that = this;
        this.fetcher(function( friends ) {
            friends || (friends = []);
            that.dropdownFetch( keyword, joms._.bind( that.dropdownUpdate, that ), friends );
        });
    }
};

Tagging.prototype.dropdownUpdate = function( matches ) {
    var html, item, cname, i, length;

    if ( !( matches && matches.length ) ) {
        this.dropdownHide();
        return;
    }

    html = '';
    cname = cssDropdownItem.substr(1);
    length = Math.min( 10, matches.length );
    for ( i = 0; i < length; i++ ) {
        item = matches[ i ];
        html += '<a href="javascript:" class=' + cname + ' data-id="' + item.id +  '" data-name="' + item.name + '">';
        html += '<img src="' + item.img + '">' + item.name + '</a>';
    }

    this.dropdownShow( html );
};

Tagging.prototype.dropdownShow = function( html ) {
    this.$dropdown.html( html ).show();
    this.dropdownIsOpened = true;
    this.dropdownSelectedItem = false;
};

Tagging.prototype.dropdownHide = function() {
    this.$dropdown.hide();
    this.dropdownIsOpened = false;
};

Tagging.prototype.dropdownOnMouseEnter = function( e ) {
    this.dropdownChangeItem( e );
};

Tagging.prototype.dropdownOnMouseDown = function() {
    this.dropdownIsClicked = true;
};

Tagging.prototype.dropdownOnMouseUp = function( e ) {
    this.dropdownSelectItem( e );
    this.dropdownIsClicked = false;
    this.dropdownHide();
};

Tagging.prototype.dropdownChangeItem = function( e ) {
    var className = cssDropdownItemActive.substr(1),
        elem, sibs, next;

    if ( typeof e !== 'number' ) {
        elem = this.dropdownSelectedItem = $( e.target );
        sibs = elem.siblings( cssDropdownItemActive );
        elem.addClass( className );
        sibs.removeClass( className );
        return;
    }

    elem = this.$dropdown.children( cssDropdownItemActive );
    if ( !elem.length ) {
        elem = this.dropdownSelectedItem = this.$dropdown.children()[ e === VK_KEYUP ? 'last' : 'first' ]();
        elem.addClass( className );
        return;
    }

    next = elem[ e === VK_KEYUP ? 'prev' : 'next' ]();
    elem.removeClass( className );
    if ( next.length ) {
        this.dropdownSelectedItem = next;
        next.addClass( className );
    } else {
        this.dropdownSelectedItem = false;
    }
};

Tagging.prototype.dropdownSelectItem = function( e ) {
    var el       = e ? $( e.currentTarget ) : this.dropdownSelectedItem,
        id       = el.data('id'),
        name     = el.data('name'),
        cpos     = this.textarea.selectionStart,
        substr   = this.textarea.value.substr( 0, cpos ),
        index    = substr.lastIndexOf('@'),
        re, value;

    this.tagsAdded || (this.tagsAdded = []);
    this.tagsAdded.push({
        id     : id,
        name   : name,
        start  : index,
        length : name.length
    });

    re = new RegExp( '^([\\s\\S]{' + index + '})[\\s\\S]{' + ( cpos - index ) + '}' );
    value = this.textarea.value.replace( re, '$1' + name );
    this.textarea.value = value;
    this.inputAutogrow();

    this.beautifierUpdate( value, this.tagsAdded );
    this.hiddenUpdate( value, this.tagsAdded );
    this.dropdownHide();
};

// Public.
Tagging.prototype.clear = function() {
    this.tagsAdded = [];
    this.$textarea && this.$textarea.val('');
    this.$hidden && this.$hidden.val('');
    this.$beautifier && this.$beautifier.empty();
};

// Exports.
return function( textarea, extraFetch ) {
    var instance = $( textarea ).data( namespace );

    if ( instance ) {
        return instance;
    } else {
        return new Tagging( textarea, extraFetch );
    }
};

});

define("utils/tagging", function(){});

(function( root, $, factory ) {

    joms.util || (joms.util = {});
    joms.util.validation = factory( root, $ );

})( window, joms.jQuery, function( window, $ ) {

var STATUS_VALID = 'valid',
    STATUS_INVALID = 'invalid';

function getErrorContainer( el ) {
    var wrapper, error;

    el = $( el );
    wrapper = el.parents('.joms-select--wrapper, .joms-textarea__wrapper, .joms-location__wrapper, .joms-checkbox--wrapper, .joms-radio--wrapper');
    error = wrapper.length ? wrapper.next('p.joms-help') : el.next('p.joms-help');

    if ( !error.length ) {
        error = $('<p class="joms-help" style="color:red">');
        error.hide().insertAfter( wrapper.length ? wrapper : el );
    }

    return error;
}

function addRequiredSign() {
    $('.joms-form__group').find('[required]')
        .add( $('.joms-form__group').find('[data-required]') )
        .each(function() {
            var el = $( this ),
                par = el.closest('.joms-form__group'),
                label = par.children().first();

            if ( !label.find('.joms-required').length ) {
                label.append(' <span class="joms-required">*</span>');
            }
        });
}

function addTextareaMaxChars() {
    var textarea = $('.joms-form__group').find('textarea[data-maxchars]');

    textarea.css('display', 'inline');
    textarea.wrap('<div style="position:relative"></div>');
    textarea.parent().append('<div class="joms-js--textarea-counter" style="position:absolute;bottom:2px;right:5px"></div>');
    textarea.each(function() {
        var $el = $( this );
        $el.siblings('.joms-js--textarea-counter').html( $el.data('maxchars') );
    });

    textarea.off('input').on( 'input', function() {
        var $el = $( this ),
            $counter = $el.siblings('.joms-js--textarea-counter'),
            maxChars = $el.data('maxchars') || 0,
            val;

        if ( maxChars ) {
            val = $el.val();
            if ( val.length > maxChars ) {
                val = val.substr( 0, maxChars );
                $el.val( val );
            }
            $counter.html( maxChars - val.length );
        }
    });
}

function addTextareaCharChecker() {
    var textarea = $('.joms-textarea__wrapper').find('.joms-textarea--limit'),
        errClass = 'joms-textarea--error',
        evtName = 'input.joms-textarea-limit';

    textarea.off( evtName ).on( evtName, function() {
        var $el = $( this ),
            $wrapper = $el.parent('.joms-textarea__wrapper'),
            $counter = $wrapper.find('.joms-textarea__limit > span > span'),
            minChars = $el.data('min-char'),
            maxChars = $el.data('max-char'),
            val = $el.val(),
            len = val.length;

        if ( !$wrapper.length ) {
            $wrapper = $el;
        }

        // normalize min/max characters bound
        minChars = isNaN( minChars ) ? 0 : Math.max( 0, +minChars );
        maxChars = isNaN( maxChars ) ? false : Math.max( minChars, +maxChars );

        if ( len < minChars ) {
            if ( !$wrapper.hasClass( errClass )) {
                $wrapper.addClass( errClass );
            }
        } else {
            if ( $wrapper.hasClass( errClass )) {
                $wrapper.removeClass( errClass );
            }
            if ( maxChars !== false && len > maxChars ) {
                val = val.substr( 0, maxChars );
                $el.val( val );
            }
        }

        $wrapper.css('display', 'inline-block');
        $counter.html( val.length );
        setTimeout(function() {
            $wrapper.css('display', '');
        }, 0 );
    });
}

function addValidationTrigger() {
    var evtSuffix = 'joms-validation',
        evtFocus = 'focus.' + evtSuffix,
        evtBlur = 'blur.' + evtSuffix,
        evtChange = 'change.' +evtSuffix,
        evtValidate = 'validate.' + evtSuffix,
        $form = $( '.joms-form__group' ),
        $fields = $form.find( '[required]' ).add( $form.find( '[data-required]' ) );

    $fields
        .off( evtChange ).on( evtChange, function( e, callback) {
            var $el = $(this);
            if ($el.attr('type') === 'checkbox' || $el.attr('type') === 'radio') {
                $el.trigger( evtValidate, callback );
            }
        })
        .off( evtFocus ).on( evtFocus, function() {
            var $el = $( this );
            $el.data( 'currentValue', $el.val() );
        })
        .off( evtBlur ).on( evtBlur, function( e, callback ) {
            var $el = $( this ),
                currentValue = $el.data( 'currentValue' );

            if ( typeof currentValue !== 'undefined' && $el.val() !== currentValue ) {
                $el.trigger( evtValidate, callback );
            }
        })
        .off( evtValidate ).on( evtValidate, function( e, callback ) {
            var $el = $( this ),
                $error = getErrorContainer( $el ),
                $label = $el.closest( '.joms-form__group' ).children().first(),
                name = $el.attr( 'name' ),
                type = ( $el.attr( 'type' ) || '' ).toLowerCase(),
                tagName = $el.prop( 'tagName' ).toLowerCase(),
                label = $.trim( $label.text().replace( /\*/g, '' ) ),
                val = $.trim( $el.val() ),
                validation;

            if ( typeof callback !== 'function' ) {
                callback = function() {
                    var verify = $el.data( 'verify' ),
                        $verify;

                    // Trigger validation if confirmation field is not empty.
                    if ( verify ) {
                        $verify = $( verify );
                        if ( $verify.length && $.trim( $verify.val() ) ) {
                            $verify.trigger( evtValidate );
                        }
                    }
                };
            }
            
            if ( type.match( /^(text|password)$/ ) || tagName.match( /^(select|textarea)$/ ) ) {
                if ( ! val ) {
                    setMessage( $el, [ name, label, 'COM_COMMUNITY_REGISTER_INVALID_VALUE' ] );
                    callback( STATUS_INVALID );
                } else {
                    validation = $el.data( 'validation' ) || '';
                    if ( validation === 'username' ) {
                        validateUsername( $el, val, callback, $el.data( 'current' ) || '' );
                    } else if ( validation === 'email' ) {
                        validateEmail( $el, val, callback );
                    } else if ( validation.match( /^email:/ ) ) {
                        validateEmailConfirmation( $el, val, callback );
                    } else if ( validation === 'password' ) {
                        validatePassword( $el, val, callback );
                     } else if ( validation.match( /^password:/ ) ) {
                        validatePasswordConfirmation( $el, val, callback );
                    } else {
                        $error.fadeOut();
                        callback( STATUS_VALID );
                    }
                }
            } else if (type.match( /^(radio|checkbox)$/ )) {
                var checked = $('[name="'+name+'"]:checked').length;
                if (!checked) {
                    setMessage( $el, [ name, label, 'COM_COMMUNITY_REGISTER_INVALID_VALUE' ] );
                    callback( STATUS_INVALID );
                } else {
                    $error.fadeOut();
                    callback( STATUS_VALID );
                }
                 
            }
        });
}

function validateUsername( el, username, callback, current ) {
    joms.ajax({
        func: 'register,ajaxCheckUserName',
        data: [ username, current ],
        callback: function( json ) {
            var error = getErrorContainer( el );
            if ( json.error ) {
                error.html( json.error );
                error.show();
                callback( STATUS_INVALID );
            } else {
                error.fadeOut();
                callback( STATUS_VALID );
            }
        }
    });
}

function validateEmail( el, email, callback ) {
    var reEmail = /^([*+!.&#$¦\'\\%\/0-9a-z^_`{}=?~:-]+)@(([0-9a-z-]+\.)+[0-9a-z]{2,8})$/i;

    if ( !reEmail.test( email ) ) {
        setMessage( el, [ '', '', 'COM_COMMUNITY_INVALID_EMAIL' ]);
        callback( STATUS_INVALID );
        return;
    }

    joms.ajax({
        func: 'register,ajaxCheckEmail',
        data: [ email ],
        callback: function( json ) {
            var error = getErrorContainer( el );
            if ( json.error ) {
                error.html( json.error );
                error.show();
                callback( STATUS_INVALID );
            } else {
                error.fadeOut();
                callback( STATUS_VALID );
            }
        }
    });
}

function validateEmailConfirmation( el, value, callback ) {
    var data, ref, error;

    data  = el.data('validation').split(':');
    ref   = $( data[1] );
    error = getErrorContainer( el );

    if ( !ref.length ) {
        error.fadeOut();
        callback( STATUS_VALID );
        return;
    }

    if ( value !== ref.val() ) {
        setMessage( el, [ '', '', 'COM_COMMUNITY_REGISTER_EMAIL_NOT_SAME' ]);
        callback( STATUS_INVALID );
        return;
    }

    error.fadeOut();
    callback( STATUS_VALID );
}

function validatePassword( el, value, callback ) {
    joms.ajax({
        func: 'register,ajaxCheckPass',
        data: [ value ],
        callback: function( json ) {
            var error = getErrorContainer( el );
            if ( json.error ) {
                error.html( json.error.replace(/\n/g, '<br/>') );
                error.show();
                callback( STATUS_INVALID );
            } else {
                error.fadeOut();
                callback( STATUS_VALID );
            }
        }
    });
}

function validatePasswordConfirmation( el, value, callback ) {
    var data, ref, error;

    data  = el.data('validation').split(':');
    ref   = $( data[1] );
    error = getErrorContainer( el );

    if ( !ref.length ) {
        error.fadeOut();
        callback( STATUS_VALID );
        return;
    }

    if ( value !== ref.val() ) {
        setMessage( el, [ '', '', 'COM_COMMUNITY_REGISTER_PASSWORD_NOT_SAME' ]);
        callback( STATUS_INVALID );
        return;
    }

    error.fadeOut();
    callback( STATUS_VALID );
}

function validate( $form, callback ) {
    var errors = 0,
        counter = 0,
        form, fields, fieldsCount, checkbox, radio;

    form = $( $form );
    fields = form.find('[required]').add( form.find('[data-required]') );
    fieldsCount = fields.length;

    if ( !fields.length ) {
        callback( errors );
    }

    fields.each(function() {
        var $el = $( this );
        $el.trigger( 'validate.joms-validation', function( result ) {
            if ( result === STATUS_INVALID ) {
                errors++;
            }
            if ( ++counter >= fieldsCount ) {
                callback( errors );
            }
        });
    });

    return false;
}

function setMessage( el, data ) {
    joms.ajax({
        func: 'register,ajaxSetMessage',
        data: data,
        callback: function( json ) {
            var error = getErrorContainer( el );
            error.html( json.message );
            error.show();
        }
    });
}

function start() {
    addRequiredSign();
    addTextareaMaxChars();
    addTextareaCharChecker();
    addValidationTrigger();
}

function stop() {

}

// Exports.
return {
    start: start,
    stop: stop,
    validate: validate
};

});

define("utils/validation", function(){});

(function( root, $, factory ) {

    joms.util || (joms.util = {});
    joms.util.video = factory( root, $ );

    define('utils/video',[ 'utils/loadlib' ], function() {
        return joms.util.video;
    });

})( window, joms.jQuery, function( window, $ ) {

var player = {};

player._initMediaElement = function( id, type, data ) {
    if ( type === 'file' && data.fileType === 'flv' ) {
        joms.util.loadLib( 'flowplayer', function () {
            window.flowplayer( id, {
                    src: joms.ASSETS_URL + 'flowplayer/flowplayer-3.2.7.swf',
                    wmode: 'opaque'
                }, {
                    streamingServer: 'lighttpd',
                    playlist: [{
                        url: data.filePath,
                        autoPlay: false,
                        autoBuffering: true,
                        provider: 'lighttpd',
                        scaling: 'scale'
                    }],
                    plugins: {
                        lighttpd: {
                            url: joms.ASSETS_URL + 'flowplayer/flowplayer.pseudostreaming-3.2.7.swf',
                            queryString: window.escape('?target=${start}')
                        },
                        controls: {
                            url: joms.ASSETS_URL + 'flowplayer/flowplayer.controls-3.2.5.swf'
                        }
                    }

                }
            );
        });

    } else {
        joms.util.loadLib( 'mediaelement', function () {
            var $elem = $( '#' + id ).css({ visibility: '' });

            var options = {
                iPadUseNativeControls: type === 'file' ? true : false,
                iPhoneUseNativeControls: type === 'file' ? true : false,
                success: function( me, el, pl ) {
                    setTimeout(function() {
                        pl.disableControls();
                        pl.enableControls();
                    }, 1 );

                    if ( me.pluginType === 'flash' ) {
                        me.addEventListener( 'canplay', function() {
                            me.play();
                        }, false );
                    } else if ( joms.mobile && ( ( me.pluginType === 'youtube' ) || ( me.pluginType === 'vimeo' ) ) ) {
                        // do nothing
                    } else {
                        me.play();
                    }
                }
            };

            // #638 Play video on firefox is not as good as on chrome.
            if ( type === 'youtube' ) {
                options.defaultVideoWidth = $elem.width();
                options.defaultVideoHeight = $elem.height();
            }

            $elem.mediaelementplayer( options );
        });
    }
}

player.responsivePlayer = function( $ct, data) {
    var $media = $ct.find('.joms-media__thumbnail'),
        $player = $('<div class="joms-media__responsive-player"></div>'),
        maxHeight = 500,
        ctWidth,
        height, width, ratio;

    ctWidth = $ct.width();

    ratio = ( data.width / data.height ) || 16/9;

    height = ctWidth / ratio;

    if (height > maxHeight) {
        height = maxHeight;
        width = height * ratio;
    } else {
        width = ctWidth;
    }

    $player.css({
        width: width,
        height: height,
        margin: '0 auto'
    });

    $ct.addClass('being-played');
    $player.html('<iframe src="'+ data.src +'" width="'+width+'" height="'+height+'" '+ data.options +'"></iframe>');
    
    $media.html($player);
}

player._play_file = function( $ct, data ) {
    var $player = $ct.find('.joms-media__thumbnail'),
        $video, id, fileType;

    id = joms._.uniqueId('joms-js--video-');
    fileType = data.path.match(/\.flv$/) ? 'flv' : 'mp4';

    if ( fileType === 'flv' ) {
        $video = $(
            '<div class="flowplayer" id="' + id + '" style="width:100%;height:281px;"></div>');
    } else {
        $video = $(
            '<video id="' + id + '" width="480" height="360" preload="metadata" autoplay="autoplay">' +
            '<source src="' + data.path + '" type="video/mp4" />' +
            '</video>'
        );
    }

    $ct.addClass('being-played');
    $player.html( $video );
    player._initMediaElement( id, data.type, {
        fileType: fileType,
        filePath: data.path
    });
}

player._play_youtube = function( $ct, data ) {
    window.joms_videoplayer_native ?  player._play_YoutubeNativePlayer( $ct, data ) : player._play_YoutubeJomsPlayer( $ct, data );
}

player._play_YoutubeJomsPlayer = function( $ct, data ) {
    var id, path, $video, $player;

    id = joms._.uniqueId('joms-js--video-');

    path = data.path;
    if (joms.ios) {
        path = path.replace(/#.*$/, '');
        path = path.replace(/&t=\d+/, '');
    }

    $video = $(
        '<video id="' + id + '" controls="control" preload="none">' +
        '<source src="' + path + '" type="video/youtube" />' +
        '</video>'
    );

    $video.css({ visibility: 'hidden' });

    $player = $ct.find('.joms-media__thumbnail');
    if ( ! $player.length ) {
        $player = $ct;
    }

    $ct.addClass('being-played');
    $player.html( $video );
    player._initMediaElement( id, data.type );
}

player._play_YoutubeNativePlayer = function( $ct, data ) {
    var $player = $ct.find('.joms-media__thumbnail');
    if ( ! $player.length ) {
        $player = $ct;
    }

    $ct.addClass('being-played joms-media--video-native');
    $player.html(
        '<iframe src="//www.youtube.com/embed/' + data.id +
        '?autoplay=1&rel=0" width="500" height="281" frameborder="0" allowfullscreen></iframe>'
    );
}

player._play_vimeo = function( $ct, data ) {
    data.src = '//player.vimeo.com/video/' + data.id + '?autoplay=1';
    data.options = 'frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen';
    
    player.responsivePlayer( $ct, data );
}

player._play_myspace = function( $ct, data ) {
    var $player = $ct.find('.joms-media__thumbnail'),
        path = 'https://media.myspace.com/play/video/'+data.id;
    
    $ct.addClass('being-played');
    $player.html('<iframe src="'+path+'" frameborder="0" width="500" height="281" AllowFullScreen></iframe>');
}

player._play_blip = function( $ct, data ) {
    var $player = $ct.find('.joms-media__thumbnail'),
        path = '//blip.tv/play/' + data.id;

    $ct.addClass('being-played');
    $player.html( '<iframe src="' + path + '" width="500" height="281" frameborder="0" allowfullscreen></iframe>' );
}

player._play_dailymotion = function( $ct, data ) {
    var $player = $ct.find('.joms-media__thumbnail');

    $ct.addClass('being-played');
    $player.html( '<iframe frameborder="0" width="500" height="270" src="//www.dailymotion.com/embed/video/'+data.id+'?autoPlay=1" allowfullscreen="" allow="autoplay"></iframe>' );
}


player._play_liveleak = function( $ct, data ) {
    var $player = $ct.find('.joms-media__thumbnail'),
        path = '//www.liveleak.com/ll_embed?i=' + data.id;

    $ct.addClass('being-played');
    $player.html( '<iframe src="' + path + '" width="500" height="281" frameborder="0" allowfullscreen></iframe>' );
}

player._play_yahoo = function( $ct, data ) {
    var $player = $ct.find('.joms-media__thumbnail'),
        path = data.path;

    path = path.replace('www.yahoo.com/movies/v', 'movies.yahoo.com/video');
    path = path + '?format=embed&player_autoplay=true';

    $ct.addClass('being-played');
    $player.html( '<iframe src="' + path + '" width="500" height="281" frameborder="0" allowfullscreen="true" mozallowfullscreen="true" webkitallowfullscreen="true" allowtransparency="true"></iframe>' );
}

player._play_metacafe = function( $ct, data ) {
    var $player = $ct.find('.joms-media__thumbnail'),
        path = 'http://www.metacafe.com/embed/' + data.id + '/';

    $ct.addClass('being-played');
    $player.html( '<iframe src="' + path + '" width="500" height="281" frameborder="0" allowfullscreen></iframe>' );
}

player._play_funnyordie = function( $ct, data ) {
    var $player = $ct.find('.joms-media__thumbnail'),
        path = '//www.funnyordie.com/embed/' + data.id + '/';

    $ct.addClass('being-played');
    $player.html( '<iframe src="'+path+'" width="500" height="300" frameborder="0" allowfullscreen webkitallowfullscreen mozallowfullscreen></iframe>' );
}

player._play_collegehumor = function( $ct, data ) {
    var $player = $ct.find('.joms-media__thumbnail'),
        path = 'http://www.collegehumor.com/e/' + data.id;

    $ct.addClass('being-played');
    $player.html( '<iframe src="'+path+'" width="500" height="281" frameborder="0" allowfullscreen webkitallowfullscreen mozallowfullscreen></iframe>' );
    
}

player._play_flickr = function( $ct, data ) {
    var $player = $ct.find('.joms-media__thumbnail'),
        path = 'https://www.flickr.com/photos/'+data.id;

    $ct.addClass('being-played');
    $player.html('<a data-flickr-embed="true" href="'+path+'" ><img src="https://farm2.staticflickr.com/1729/42437754852_936a2d5e9f_b.jpg" width="1024" height="576" alt="flickr"></a><script async src="https://embedr.flickr.com/assets/client-code.js" charset="utf-8"></script>');
}

player._play_dotsub = function( $ct, data ) {
    var $player = $ct.find('.joms-media__thumbnail'),
        path = 'https://dotsub.com/media/'+data.id+'/embed/';
    
    $ct.addClass('being-played');
    $player.html('<iframe src="'+path+'" frameborder="0" width="500" height="281" AllowFullScreen></iframe>');
}

player._play_gloria = function( $ct, data ) {
    var $player = $ct.find('.joms-media__thumbnail'),
        path = 'https://gloria.tv/video/'+data.id+'/embed/';
    
    $ct.addClass('being-played');
    $player.html('<iframe src="'+path+'" frameborder="0" width="500" height="265" AllowFullScreen></iframe>');
}

player._play_sapo = function( $ct, data ) {
    var $player = $ct.find('.joms-media__thumbnail'),
        path = 'http://rd.videos.sapo.pt/playhtml?file=http://rd.videos.sapo.pt/'+data.id+'/mov/1';
    
    $ct.addClass('being-played');
    $player.html('<iframe src="'+path+'" frameborder="0" width="500" height="265" AllowFullScreen></iframe>');
}

player._play_facebook = function( $ct, data ) {
    data.src = 'https://www.facebook.com/plugins/video.php?href='+encodeURIComponent('https://www.facebook.com/'+data.id) +'&show_text=false';
    data.options = 'style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowTransparency="true" allow="encrypted-media"';
    
    player.responsivePlayer( $ct, data );
}

player._play_soundcloud = function( $ct, data ) {
    var $player = $ct.find('.joms-media__thumbnail'),
        path = 'https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/'+data.id+'&color=%23ff5500&auto_play=true&hide_related=false&show_comments=false&show_user=false&show_reposts=false&show_teaser=true&visual=true';

    $ct.addClass('being-played');
    $player.html('<iframe src="'+path+'" width="100%" height="166" scrolling="no" frameborder="no" allow="autoplay" ></iframe>');
}

player._play_godtube = function( $ct, data ) {
    var $player = $ct.find('.joms-media__thumbnail'),
        splitted = data.id.split('|'),
        videoID = splitted[0],
        type = splitted[1],
        embedID = splitted[2];

    if (type === 'godtube') {
        $ct.addClass('being-played');
        var path = 'https://www.godtube.com/embed/watch/'+splitted[0].toLowerCase()+'/?w=728&h=408&ap=true&sl=true&title=true&dp=true';
        $player.html('<iframe width="500" height="250" aspectratio="16:9" frameborder="0" scrolling="no" src="'+path+'"></iframe>"');
    } 
    else if (type === 'youtube') {
        $ct.addClass('being-played');
        var xdata = {
            id: embedID,
            path: 'https://www.youtube.com/watch?v='+embedID,
            type: type
        }
        player._play_youtube( $ct, xdata );
    }
    else {
        player._play_other( $ct, data );
    }

}

player._play_rutube = function( $ct, data ) {
    var $player = $ct.find('.joms-media__thumbnail'),
        path = '//rutube.ru/play/embed/'+data.id;

    $ct.addClass('being-played');
    $player.html('<iframe src="'+path+'" frameborder="0" width="500" height="265" AllowFullScreen></iframe>');
}

player._play_ku6 = function( $ct, data ) {
    var $player = $ct.find('.joms-media__thumbnail');

    $ct.addClass('being-played');
    $player.html('<iframe src="'+data.id+'" frameborder="0" width="500" height="265" AllowFullScreen></iframe>');
}

player._play_twitch = function( $ct, data ) {
    var $player = $ct.find('.joms-media__thumbnail'),
        videoid = data.id.split('|')[0],
        type = data.id.split('|')[1],
        path;
    
    $ct.addClass('being-played');
    if (type === 'clip') {
        path ='https://clips.twitch.tv/embed?autoplay=true&clip='+videoid;
    } else {
        path ='https://player.twitch.tv/?autoplay=true&video='+videoid;
    }
    
    $player.html('<iframe src="'+path+'" frameborder="0" allowfullscreen="true" scrolling="no" height="265" width="500"></iframe>');
}

player._play_flic = function( $ct, data ) {
    player._play_flickr( $ct, data );
}

player._play_vbox7 = function( $ct, data ) {
    var $player = $ct.find('.joms-media__thumbnail'),
        path ='https://www.vbox7.com/emb/external.php?vid=' + data.id + '&autoplay=1';

    $ct.addClass('being-played');
    $player.html('<iframe src="'+path+'" frameborder="0" allowtransparency="true" webkitallowfullscreen mozallowfullscreen allowfullscreen height="265" width="500"></iframe>');
}

player._play_veoh = function( $ct, data ) {
    var $player = $ct.find('.joms-media__thumbnail');
    $ct.addClass('being-played');
    $player.html([
        '<object width="500" height="280">',
			'<embed src="http://www.veoh.com/swf/webplayer/WebPlayer.swf?version=AFrontend.5.7.0.1509&permalinkId=' + data.id + '&player=videodetailsembedded&videoAutoPlay=1&id=anonymous"',
				'type="application/x-shockwave-flash"',
				'allowscriptaccess="always"',
				'allowfullscreen="true"',
				'width="500"',
				'height="280"',
				'>',
			'</embed>',
		'</object>'
    ].join(''));
}

player._play_videa = function( $ct, data ) {
    var $player = $ct.find('.joms-media__thumbnail');
    $ct.addClass('being-played');
    $player.html('<iframe width="500" height="280" src="//videa.hu/player?v='+data.id+'&autoplay=1" allowfullscreen="allowfullscreen" webkitallowfullscreen="webkitallowfullscreen" mozallowfullscreen="mozallowfullscreen" frameborder="0"></iframe>');
}

player._play_youku = function( $ct, data ) {
    var $player = $ct.find('.joms-media__thumbnail');
    $ct.addClass('being-played');
    $player.html('<iframe height=280 width=500 src="http://player.youku.com/embed/' + data.id + '" frameborder=0 allowfullscreen></iframe>');
}

player._play_other = function( $ct, data ) {
    window.open( data.path );
}
    
var play = function ( $ct, data ) {
    var fn = '_play_' + data.type;
    if ( typeof player[fn] === 'function') {
        player[fn]( $ct, data);
    } else {
        player._play_other( $ct, data );
    }
}

// Exports.
return {
    play: play
};

});

(function( root, $, factory ) {

    joms.util || (joms.util = {});
    joms.util.wysiwyg = factory( root, $ );

    define('utils/wysiwyg',[ 'utils/loadlib' ], function() {
        return joms.util.wysiwyg;
    });

})( window, joms.jQuery, function( window, $ ) {

function start() {
    var editor = $('textarea').filter('[data-wysiwyg=trumbowyg]');

    if ( !editor.length ) {
        return;
    }

    joms.util.loadLib( 'trumbowyg', function() {
        trumbowygTranslate();

        // Check RTL.
        var isRTL = false;
        if ( $('html').attr('dir') === 'rtl' ) {
            isRTL = true;
        }

        // TODO: Set upload path.
        $.extend( jQuery.trumbowyg, {
            upload: {
                serverPath: joms.BASE_URL + 'index.php?option=com_community&view=photos&task=ajaxPreviewComment&isEditor=1'
            }
        });

        editor.each(function() {
            var btns, config, instance;

            btns = $( this ).data( 'btns' );
            btns = btns || 'viewHTML,|,bold,italic,underline,|,unorderedList,orderedList,|,link,image';
            btns = btns.split(',');

            config = {
                btnsDef: {
                    image: {
                        dropdown: [ 'insertImage', 'upload' ],
                        ico: 'insertImage'
                    }
                },
                btns: btns,
                fullscreenable: false,
                mobile: false,
                tablet: false,
                removeformatPasted: true,
                autogrow: true
            };

            if ( isRTL ) {
                config.dir = 'rtl';
            }

            instance = $( this ).trumbowyg( config )
                .on('tbwblur', function() {
                    var t = $( this ).data('trumbowyg');
                    t.syncCode();
                })
                .data('trumbowyg');

            // Override modal button render.
            instance.buildModalBtn = trumbowygBuildModalBtn;

            // Override modal input.
            instance._openModalInsert = instance.openModalInsert;
            instance.openModalInsert = function( title, fields, cmd ) {
                var modBox = instance._openModalInsert( title, fields, cmd );

                modBox.find('label').each(function() {
                    var label = $( this ),
                        input = label.find('input'),
                        name = input.attr('name'),
                        type = input.attr('type'),
                        html;

                    if ([ 'url', 'file', 'title', 'text', 'target', 'alt' ].indexOf( name ) >= 0 ) {
                        html  = '<div class="joms-form__group" style="text-align:left">';
                        html += '<span style="width:90px;text-align:center">' + label.find('.trumbowyg-input-infos').text() + '</span>';
                        if ( type === 'file' ) {
                            html += '<input name="' + name + '" class="joms-input" value="' + ( input.val() || '' ) + '" type="file"';
                            html += ' accept="image/png,image/jpeg,image/gif,image/bmp">';
                        } else {
                            html += '<input name="' + name + '" class="joms-input" value="' + ( input.val() || '' ) + '" type="' + ( type || 'text' ) + '">';
                        }
                        html += '</div>';
                        label.replaceWith( $(html) );
                    }
                });

                return modBox;
            };
        });
    });
}

function trumbowygBuildModalBtn( name, modal ) {
    return $('<button/>', {
        'class': 'joms-button--full-small joms-button--' + ( name === 'submit' ? 'primary' : 'neutral' ),
        'type': name,
        'text': this.lang[name] || name
    }).appendTo( modal.find('form') );
}

function trumbowygTranslate() {
    $.extend( jQuery.trumbowyg.langs.en, window.joms_lang.wysiwyg || {});
}

// Exports.
return {
    start: start
};

});

(function( root, $, factory ) {

    joms.fn || (joms.fn = {});
    joms.fn.tagging = factory( root, $ );

    define('functions/tagging',[ 'utils/tagging' ], function() {
        return joms.fn.tagging;
    });

})( window, joms.jQuery, function( window, $ ) {

var groupMembers = {},
    eventMembers = {},
    groupMembersFetching = {},
    eventMembersFetching = {},
    groupMembersFetchCallback = {},
    eventMembersFetchCallback = {};

function initInputbox() {
    var inputbox = $( document.body )
        .find('.joms-js--newcomment')
        .find('textarea.joms-textarea');

    inputbox.each(function() {
        var el = $( this );
        if ( !el[0].joms_beautifier ) {
            el[0].joms_data = el.data();
            el.jomsTagging( fetchInputbox );
        }
    });
}

function fetchInputbox( callback ) {
    var that = this,
        data = this.textarea.joms_data,
        id = data.tagId || data.id,
        func = ( data.tagFunc || data.func || '' ).toLowerCase(),
        type = data.type || '',
        friends = [],
        url;

    if ( this.textarea.joms_friends ) {
        callback( this.textarea.joms_friends );
        return;
    }

    if ( !func ) {
        url = 'index.php?option=com_community&view=friends&task=ajaxAutocomplete&type=comment&streamid=' + id;
        if ( window.joms_group_id ) {
            url += '&groupid=' + window.joms_group_id;
        } else if ( window.joms_event_id ) {
            url += '&eventid=' + window.joms_event_id;
        }
    } else {
        url = 'index.php?option=com_community&view=friends&task=ajaxAutocomplete';
        if ( func.indexOf('album') > -1 ) {
            url += '&albumid=' + id;
        } else if ( func.indexOf('photo') > -1 ) {
            url += '&photoid=' + id + '&rule=photo-comment';
        } else if ( func.indexOf('video') > -1 ) {
            url += '&videoid=' + id;
        } else if ( func.indexOf('discussion') > -1 ) {
            url += '&discussionid=' + id;
        } else if ( func.indexOf('inbox') > -1 ) {
            url += '&msgid=' + id;
        } else if ( type.match( /service\.comment\.joomla\.article/ ) ) {
            url += '&type=comment&streamid=' + id;
        }
    }

    this.fetchXHR && this.fetchXHR.abort();
    this.fetchXHR = $.ajax({
        url: joms.BASE_URL + url,
        dataType: 'json',
        success: function( json ) {
            that.textarea.joms_friends = friends = _parse( json );
        },
        complete: function() {
            var i, j, ilen, jlen;

            // Update (posibbly) old images and names.
            if ( friends.length && window.joms_friends.length ) {
                for ( i = 0, ilen = Math.min( friends.length, 30 ); i < ilen; i++ ) {
                    for ( j = 0, jlen = Math.min( window.joms_friends.length, 30 ); j < jlen; j++ ) {
                        if ( +friends[i].id === +window.joms_friends[j].id ) {
                            window.joms_friends[j].avatar = friends[i].avatar;
                            window.joms_friends[j].name = friends[i].name;
                        }
                    }
                }
            }

            that.fetchXHR = false;
            callback( friends );
        }
    });
}

function fetchFriendsInContext() {
    var url  = 'index.php?option=com_community&view=friends&task=ajaxAutocomplete',
        friends = [];

    if ( window.joms_group_id ) {
        url += '&groupid=' + window.joms_group_id;
    } else if ( window.joms_event_id ) {
        url += '&eventid=' + window.joms_event_id;
    } else {
        url += '&allfriends=1';
    }

    joms.jQuery.ajax({
        url: joms.BASE_URL + url,
        dataType: 'json',
        success: function( json ) {
            friends = _parse( json );
        },
        complete: function() {
            window.joms_friends = friends;
        }
    });
}

function fetchGroupMembers( groupid, callback ) {
    var url  = 'index.php?option=com_community&view=friends&task=ajaxAutocomplete&groupid=' + groupid;

    if ( !groupMembersFetchCallback[ groupid ] ) {
        groupMembersFetchCallback[ groupid ] = [];
    }

    if ( groupMembersFetching[ groupid ] ) {
        groupMembersFetchCallback[ groupid ].push( callback );
        return;
    }

    if ( groupMembers[ groupid ] ) {
        callback( groupMembers[ groupid ] );
        return;
    }

    groupMembersFetching[ groupid ] = true;
    joms.jQuery.ajax({
        url: joms.BASE_URL + url,
        dataType: 'json',
        success: function( json ) {
            groupMembers[ groupid ] = _parse( json );
        },
        complete: function() {
            callback( groupMembers[ groupid ] );
            while ( groupMembersFetchCallback[ groupid ].length ) {
                try {
                    ( groupMembersFetchCallback[ groupid ].shift() )( groupMembers[ groupid ] );
                } catch (e) {}
            }
            groupMembersFetching[ groupid ] = false;
        }
    });
}

function fetchEventMembers( eventid, callback ) {
    var url  = 'index.php?option=com_community&view=friends&task=ajaxAutocomplete&eventid=' + eventid;

    if ( !eventMembersFetchCallback[ eventid ] ) {
        eventMembersFetchCallback[ eventid ] = [];
    }

    if ( eventMembersFetching[ eventid ] ) {
        eventMembersFetchCallback[ eventid ].push( callback );
        return;
    }

    if ( eventMembers[ eventid ] ) {
        callback( eventMembers[ eventid ] );
        return;
    }

    eventMembersFetching[ eventid ] = true;
    joms.jQuery.ajax({
        url: joms.BASE_URL + url,
        dataType: 'json',
        success: function( json ) {
            eventMembers[ eventid ] = _parse( json );
        },
        complete: function() {
            callback( eventMembers[ eventid ] );
            while ( eventMembersFetchCallback[ eventid ].length ) {
                try {
                    ( eventMembersFetchCallback[ eventid ].shift() )( eventMembers[ eventid ] );
                } catch (e) {}
            }
            eventMembersFetching[ eventid ] = false;
        }
    });
}

function _parse( json ) {
    var uniques = [],
        friends = [],
        id, i;

    if ( json && json.suggestions && json.suggestions.length ) {
        for ( i = 0; i < json.suggestions.length; i++ ) {
            id = '' + json.data[i];
            if ( uniques.indexOf(id) >= 0 ) continue;
            uniques.push( id );
            friends.push({
                id: id,
                name: json.suggestions[i],
                avatar: json.img[i].replace( /^.+src="([^"]+)".+$/ , '$1'),
                type: 'contact'
            });
        }
    }

    return friends;
}

// Exports.
return {
    initInputbox: initInputbox,
    fetchInputbox: fetchInputbox,
    fetchFriendsInContext: fetchFriendsInContext,
    fetchGroupMembers: fetchGroupMembers,
    fetchEventMembers: fetchEventMembers
};

});

(function( root, $, factory ) {

    joms.view || (joms.view = {});
    joms.view.comment = factory( root, $ );

    define('views/comment',[ 'utils/video', 'functions/tagging' ], function() {
        return joms.view.comment;
    });

})( window, joms.jQuery, function( window, $ ) {

var container, uploader, uploaderType, uploaderParams, uploaderButton, uploaderAttachment, uploaderRef, uploaderError;

function initialize() {
    uninitialize();
    container = $( document.body );
    container.on( 'keydown.joms-comment', '.joms-comment__reply textarea', keydown );
    container.on( 'focus.joms-comment', '.joms-comment__reply textarea', focused );
    container.on( 'focus.joms-comment', '.joms-js--pm-message textarea', focused );
    container.on( 'click.joms-comment', '.joms-comment__reply .joms-js--btn-send', onSend );
    container.on( 'click.joms-comment', '.joms-js--inbox-reply .joms-js--btn-send', onSend );
    container.on( 'click.joms-comment', '.joms-comment__more', showAll );

    addAttachmentInit();
    initInputbox();
    initVideoPlayers();
}

function uninitialize() {
    if ( container ) {
        container.off( 'keydown.joms-comment', '.joms-comment__reply textarea' );
        container.off( 'focus.joms-comment', '.joms-comment__reply textarea' );
        container.off( 'focus.joms-comment', '.joms-js--pm-message textarea' );
        container.off( 'click.joms-comment', '.joms-comment__reply .joms-js--btn-send' );
        container.off( 'click.joms-comment', '.joms-js--inbox-reply .joms-js--btn-send' );
        container.off( 'click.joms-comment', '.joms-comment__more' );
    }
}

function initInputbox() {
    joms.fn.tagging.initInputbox();
}

function initVideoPlayers() {
    var initialized = '.joms-js--initialized',
        cssVideos = '.joms-js--video',
        videos = $('.joms-comment__body,.joms-js--inbox').find( cssVideos ).not( initialized ).addClass( initialized.substr(1) );

    if ( !videos.length ) {
        return;
    }

    joms.loadCSS( joms.ASSETS_URL + 'vendors/mediaelement/mediaelementplayer.min.css' );
    videos.on( 'click.joms-video', cssVideos + '-play', function() {
        var $el = $( this ).closest( cssVideos );
        joms.util.video.play( $el, $el.data() );
    });

    if ( joms.ios ) {
        setTimeout(function() {
            videos.find( cssVideos + '-play' ).click();
        }, 2000 );
    }
}

function keydown( e ) {
    var key = e.keyCode || e.charCode,
        textarea;

    if ( key !== 13 || e.shiftKey ) {
        return;
    }

    textarea = $( e.target );
    if ( textarea.data('noentersend') || joms.mobile ) {
        return;
    }

    setTimeout(function() { send( e ); }, 100 );
    return false;
}

function focused( e ) {
    var textarea = $( e.target ),
        wrapper = textarea.closest('.joms-textarea__wrapper'),
        attachment = wrapper.find('.joms-textarea__attachment');

    if ( attachment.length ) {
        uploaderAttachment = attachment;
    }
}

function onSend( e ) {
    var el = $( e.currentTarget ),
        textarea = el.closest('.joms-comment__reply,.joms-js--inbox-reply').find('textarea');

    send({ currentTarget: textarea[0] });
}

function send( e ) {
    var el = $( e.currentTarget ),
        text = ( el.val() || ''),
        isEdit = +el.data('edit'),
        id, func, type, attachment;

    // Use tag value if available.
    if ( el[0].joms_hidden ) {
        text = el[0].joms_hidden.val() || text;
    }

    // Don't send empty message and no image.
    if ( text.replace( /^\s+|\s+$/g, '' ) === '' ) {
        attachment = el.siblings('.joms-textarea__attachment');
        if ( !attachment.length || !attachment.is(':visible') ) {
            alert(joms.getTranslation('COM_COMMUNITY_CANNOT_EDIT_COMMENT_ERROR'));
            return;
        }
    }

    id = +el.data('id');
    func = el.data('func') || '';
    type = el.data('type') || '';

    if ( isEdit ) {
        editSave( el, id, func, type, text, function() {
            initVideoPlayers();
            reset( el, 'edit' );
        });
    } else {
        addSave( el, id, func, type, text, function() {
            el.val('');
            initVideoPlayers();
            reset( el );
        });
    }
}

function reset( el, type ) {
    if ( type !== 'edit' ) {
        el.closest('.joms-comment__reply').find('.joms-textarea__attachment').hide();
    }

    el = el[0];
    if ( el.joms_reset ) {
        el.joms_reset();
    }
    if ( el.joms_beautifier && el.joms_beautifier !== 'none' ) {
        el.joms_beautifier.html('');
    }
    if ( el.joms_hidden ) {
        el.joms_hidden.val( el.value );
    }
}

function addSave( el, id, func, type, text, callback ) {
    var isWall = func,
        isPhotoAlbum = false,
        isPhoto = false,
        isVideo = false,
        isDiscussion = false,
        isInbox = false,
        isCustomWall = false,
        photo = false,
        file = false,
        data, ct, funcLower, $loading;

    if ( el.data('saving') ) {
        return;
    }

    el.data( 'saving', 1 );

    if ( isWall ) {
        funcLower = func.toLowerCase();
        if ( funcLower.indexOf('album') > -1 ) {
            isPhotoAlbum = true;
        } else if ( funcLower.indexOf('photo') > -1 ) {
            isPhoto = true;
        } else if ( funcLower.indexOf('video') > -1 ) {
            isVideo = true;
        } else if ( funcLower.indexOf('discussion') > -1 ) {
            isDiscussion = true;
        } else if ( funcLower.indexOf('inbox') > -1 ) {
            isInbox = true;
        } else if ( funcLower.indexOf('wall') ) {
            isCustomWall = true;
        }
    }

    ct = uploaderAttachment;
    if ( ct && ct.is(':visible') ) {
        photo = ct.find('.joms-textarea__attachment--thumbnail').find('img');
        file = photo.siblings('b');
        if ( photo.is(':visible') ) {
            photo = photo.data('photo_id');
            file = '';
        } else if ( file.is(':visible') ) {
            file = file.data('id');
            photo = '';
        }
    }

    photo = photo || '';
    file = file || '';

    if ( !isWall ) {
        func = 'system,ajaxStreamAddComment';
        data = [ id, text, photo ];
    } else if ( isVideo || isDiscussion ) {
        data = [ text, id, photo ];
    } else if ( isInbox ) {
        data = [ id, text, photo, file ];
    } else if ( isCustomWall ) {
        data = [ type, id, text, photo ];
    } else {
        data = [ text, id, '', photo ];
    }

    $loading = $( el ).siblings('.joms-textarea__loading');
    $loading.show();

    joms.ajax({
        func: func,
        data: data,
        callback: function( json ) {
            var $ct, item, status, counter;

            $loading.hide();

            if ( json.success ) {

                if ( isInbox ) {
                    _onInboxAdded( json.html );

                    el.removeData('saving');

                    // Enable sibling items.
                    if ( uploaderRef ) {
                        uploaderRef.siblings('svg')
                            .removeData('disabled')
                            .css('opacity', '');
                    }

                    return;
                }

                $ct = $( '.joms-js--comments-' + id );
                $ct.append( json.html || '' );
                
                counter = $('.joms-comment__counter--' + id);
                counter.html( +counter.eq(0).text() + 1 );
                counter.parents('.joms-comment__status').show();
            }

            if ( typeof callback === 'function' ) {
                callback( json );
            }

            el.removeData('saving');

            // Enable sibling items.
            if ( uploaderRef ) {
                uploaderRef.siblings('svg')
                    .removeData('disabled')
                    .css('opacity', '');
            }

            if ( json.error ) {
                window.alert( json.error );
            }

        }
    });
}

function edit( commentId, elem, type ) {
    var isWall = type === 'wall',
        comment, reply, textarea;

    elem = $( elem );
    comment = elem.closest('.joms-comment__item');
    textarea = comment.children('.joms-comment__reply').find('textarea');

    if ( isWall ) {
        reply = comment.closest('.joms-comment').siblings('.joms-comment__reply');
    } else {
        reply = comment.closest('.joms-stream').children('.joms-comment__reply');
    }

    reply.hide();
    comment.children('.joms-comment__body,.joms-comment__actions').hide();
    comment.children('.joms-comment__reply').show();
    textarea.jomsTagging();
    textarea.off( 'reset.joms-tagging' );
    textarea.on( 'reset.joms-tagging', function() {
        comment.children('.joms-comment__reply').hide();
        comment.children('.joms-comment__body,.joms-comment__actions').show();
        reply.show();
    });
    textarea[0].focus();
}

function editSave( el, commentId, func, type, text, callback ) {
    var isWall = func,
        attachment, photo, photoSrc, data, $loading;

    if ( el.data('saving') ) {
        return;
    }

    el.data( 'saving', 1 );

    attachment = el.siblings('.joms-textarea__attachment');

    if ( attachment.is(':visible') ) {
        photo = attachment.find('.joms-textarea__attachment--thumbnail').find('img');
        photoSrc = photo.attr('src');
        photo = photo.data('photo_id') || '0';
    } else if ( attachment.data('no_thumb') ) {
        photo = '0';
    } else {
        photo = '-1';
    }

    if ( isWall ) {
        data = [ commentId, text, func, photo ];
        func = 'system,ajaxUpdateWall';
    } else {
        func = 'system,ajaxeditComment';
        data = [ commentId, text, photo ];
    }

    $loading = $( el ).siblings('.joms-textarea__loading');
    $loading.show();

    joms.ajax({
        func: func,
        data: data,
        callback: function( json ) {
            var $ct, $body, $reply;

            $loading.hide();

            if ( json.success ) {
                $ct = $( '.joms-js--comment-' + commentId );
                $body = $ct.find('.joms-js--comment-body');
                $reply = $( '.joms-js--newcomment-' + $ct.data('parent') );

                $ct.find('.joms-js--comment-editor').hide().find('textarea').val( json.originalComment || '' );
                $ct.find('.joms-js--comment-content').html( json.comment || '' );
                $ct.find('.joms-js--comment-actions').show();
                $body.show();
                $reply.show();

                // Update photo if available.
                if ( +photo < 0 || +photo > 0 ) {
                    $body.children('.joms-js--comment-content').next('div').remove();

                    if ( +photo > 0 && photoSrc ) {
                        $body.children('.joms-js--comment-content').after([
                            '<div style="padding:5px 0">',
                            '<a href="javascript:" onclick="joms.api.photoZoom(\'', photoSrc, '\');">',
                            '<img class="joms-stream-thumb" src="', photoSrc, '">',
                            '</a>',
                            '</div>'
                        ].join(''));
                    }
                }
            }

            if ( json.error ) {
                alert(json.error)
            }

            if ( typeof callback === 'function' ) {
                callback( json );
            }

            try {
                el.blur();
            } catch(e) {}

            el.removeData('saving');

            // Enable sibling items.
            if ( uploaderRef ) {
                uploaderRef.siblings('svg')
                    .removeData('disabled')
                    .css('opacity', '');
            }
        }
    });
}

// function editCancel( textarea ) {


// }

function like( commentId ) {
    joms.ajax({
        func: 'system,ajaxStreamAddLike',
        data: [ commentId, 'comment' ],
        callback: function( json ) {
            var $ct, btn, info;

            if ( json.success ) {
                $ct = $( '.joms-js--comment-' + commentId );

                if ( $ct.length ) {
                    btn = $ct.find('.joms-comment__actions').find('.joms-button--liked');
                    btn.attr( 'onclick', 'joms.api.commentUnlike(\'' + commentId + '\');' );
                    btn.addClass('liked');
                    btn.find('span').html( btn.data('lang-unlike') );
                    btn.find('use').attr( 'xlink:href', window.location + '#joms-icon-thumbs-down' );

                    info = $ct.find('.joms-comment__actions [data-action=showlike]');
                    if ( !json.html ) {
                        info.remove();
                    } else if ( info.length ) {
                        info.replaceWith( json.html );
                    } else {
                        btn.after( json.html );
                    }
                }
            }
        }
    });
}

function unlike( commentId ) {
    joms.ajax({
        func: 'system,ajaxStreamUnlike',
        data: [ commentId, 'comment' ],
        callback: function( json ) {
            var $ct, btn, info;

            if ( json.success ) {
                $ct = $( '.joms-js--comment-' + commentId );

                if ( $ct.length ) {
                    btn = $ct.find('.joms-comment__actions').find('.joms-button--liked');
                    btn.attr( 'onclick', 'joms.api.commentLike(\'' + commentId + '\');' );
                    btn.removeClass('liked');
                    btn.find('span').html( btn.data('lang-like') );
                    btn.find('use').attr( 'xlink:href', window.location + '#joms-icon-thumbs-up' );

                    info = $ct.find('.joms-comment__actions [data-action=showlike]');
                    if ( !json.html ) {
                        info.remove();
                    } else if ( info.length ) {
                        info.replaceWith( json.html );
                    } else {
                        btn.after( json.html );
                    }
                }

            }
        }
    });
}

function showAll( e ) {
    var el = $( e.currentTarget ),
        ct = el.closest('.joms-js--comments'),
        type = ct.data('type') || '',
        id = +ct.data('id'),
        shown = ct.children('.joms-js--comment').length,
        limit = window.joms_prev_comment_load;

    if ( !id ) {
        return;
    }

    joms.ajax({
        func: 'system,ajaxStreamShowComments',
        data: [ id, type, shown, limit ],
        callback: function( json ) {
            var html, ct, remaining, link, lang;

            if ( json.success ) {
                html = $( $.trim( json.html ) );

                if ( type ) {
                    html = html.filter('.joms-js--comments').children();
                }

                ct = $( '.joms-js--comments-' + id );
                ct.find('.joms-js--comment').remove();
                ct.append( html );

                json.total = +json.total;
                remaining = Math.max( 0, json.total - ct.children('.joms-js--comment').length );

                if ( remaining > 0 ) {
                    link = ct.find('.joms-js--more-comments a');
                    lang = link.data('lang') || (window.joms_lang.COM_COMMUNITY_SHOW_PREVIOUS_COMMENTS + ' (%d)');
                    if ( lang ) {
                        link.text( lang.replace('%d', remaining ) );
                    }
                } else {
                    ct.find('.joms-js--more-comments').remove();
                }

                initVideoPlayers();
            }
        }
    });
}

function remove( commentId, type ) {
    var cf = confirm(joms_lang.COM_COMMUNITY_ARE_YOU_SURE_YOU_WANT_TO_DELETE_THIS_COMMENT);
    if (!cf) {
        return;
    }
    var isInbox = type === 'inbox',
        isWall = type === 'wall',
        func,
        data;

    if ( isInbox ) {
        func = 'inbox,ajaxRemoveMessage';
        data = [ commentId ];
    } else if ( isWall ) {
        func = window.joms_wall_remove_func;
        data = [ commentId ];
    } else {
        func = 'system,ajaxStreamRemoveComment';
        data = [ commentId ];
    }

    joms.ajax({
        func: func,
        data: data,
        callback: function( json ) {
            var $ct;

            if ( !json.success ) {
                window.alert( json.error || 'Undefined error.' );
                return;
            }

            if ( isInbox ) {
                _onInboxRemoved( commentId );
                return;
            }

            $ct = $( '.joms-js--comment-' + commentId );

            if ( $ct.length ) {
                $ct.fadeOut( 300, function () {
                    $(this).remove();
                });
                var parent_id = $ct.data('stream-id') || $ct.data('parent');
                var counter = $( '.joms-comment__counter--' + parent_id );
                if ( counter.length ) {
                    var val = +counter.eq(0).text() - 1;
                    if (val === 0) {
                        counter.parents('.joms-comment__status').hide();
                        $ct.parents('.joms-popup').find('.joms-comment').html(joms_lang.COM_COMMUNITY_NO_COMMENTS_YET);
                    }
                    counter.html( val );
                }
            }

        }
    });
}

function removeTag( id, type ) {
    joms.ajax({
        func: 'activities,ajaxRemoveUserTag',
        data: [ id, type || 'comment' ],
        callback: function( json ) {
            var $comment, $cbutton, $ccontent, $ceditor, $textarea;

            if ( json.success ) {
                if ( type === 'inbox' ) {
                    $comment  = $( '.joms-js--inbox-item-' + id );
                    $cbutton  = $comment.find('.joms-button--remove-tag');
                    $ccontent = $comment.find('.joms-js--inbox-content');

                    $ccontent.html( json.data );
                    $cbutton.remove();
                } else {
                    $comment  = $( '.joms-js--comment-' + id );
                    $cbutton  = $comment.find('.joms-button--remove-tag');
                    $ccontent = $comment.find('.joms-js--comment-content');
                    $ceditor  = $comment.find('.joms-js--comment-editor');
                    $textarea = $ceditor.find('textarea');

                    $ccontent.html( json.data );
                    $textarea.val( json.unparsed );
                    $cbutton.remove();
                }
            }
        }
    });
}

function removePreview( id, type ) {
    var isInbox = type === 'inbox',
        isWall = type === 'wall',
        func, data;

    if ( isInbox ) {
        func = 'inbox,ajaxRemovePreview';
        data = [ id ];
    } else if ( isWall ) {
        func = 'system,ajaxRemoveWallPreview';
        data = [ id ];
    } else {
        func = 'system,ajaxRemoveCommentPreview';
        data = [ id ];
    }

    joms.ajax({
        func: func,
        data: data,
        callback: function( json ) {
            if ( !json.success ) {
                window.alert( json.error || 'Undefined error.' );
                return;
            }

            if ( isInbox ) {
                _onInboxUpdated( id, json.html );
                return;
            }

            $( '.joms-js--comment-' + id )
                .find('.joms-js--comment-preview').remove();
        }
    });
}

function removeThumbnail( id, type ) {
    var isInbox = type === 'inbox',
        isWall = type === 'wall',
        func, data;

    if ( isInbox ) {
        func = 'inbox,ajaxRemoveThumbnail';
        data = [ id ];
    } else if ( isWall ) {
        // @todo
    } else {
        // @todo
    }

    joms.ajax({
        func: func,
        data: data,
        callback: function( json ) {
            if ( !json.success ) {
                window.alert( json.error || 'Undefined error.' );
                return;
            }

            if ( isInbox ) {
                _onInboxUpdated( id, json.html );
                return;
            }

            if ( isWall ) {
                // @todo
                return;
            }

            // @todo stream
            return;
        }
    });
}

function addAttachment( elem, type, params ) {
    var maxFileSize, extensions, settings;

    elem = $( elem );
    if ( elem.data('disabled') ) {
        return;
    }

    uploaderRef = elem;
    elem = elem.siblings('.joms-textarea__wrapper');

    if ( !elem.length ) {
        return;
    }

    if ( type !== 'file' ) {
        type = 'image';
    }

    params = params || {};
    maxFileSize = +params.max_file_size;
    extensions = params.exts;

    delete params.max_file_size;
    delete params.exts;

    uploaderType = type;
    uploaderParams = type === 'file' ? params : {};

    settings = {
        file: {
            url: joms.BASE_URL + 'index.php?option=com_community&view=files&task=multiUpload',
            filters: { mime_types: [{ title: 'Document files', extensions: extensions }] },
            max_file_size: maxFileSize > 0 ? ((+maxFileSize) * 1048576) : 0
        },
        image: {
            url: joms.BASE_URL + 'index.php?option=com_community&view=photos&task=ajaxPreviewComment',
            filters: { mime_types: [{ title: 'Image files', extensions: 'jpg,jpeg,png,gif' }] },
            max_file_size: undefined
        }
    };

    addAttachmentInit( elem, function() {
        uploader.refresh();
        uploader.settings.url = settings[ type ].url;
        uploader.settings.filters = settings[ type ].filters;
        uploader.settings.max_file_size = settings[ type ].max_file_size;
        uploader.refresh();
        window.joms_webdriver || uploaderButton.click();
    });
}

function addAttachmentInit( elem, callback ) {
    if ( typeof callback !== 'function' ) {
        callback = function() {};
    }

    if ( uploader ) {
        uploaderAttachment = elem && elem.find('.joms-textarea__attachment');
        callback();
        return;
    }

    joms.util.loadLib( 'plupload', function () {
        setTimeout(function() {
            var container, button;

            container = $('<div id="joms-js--attachment-uploader" aria-hidden="true" style="width:1px; height:1px; overflow:hidden">').appendTo( document.body );
            button    = $('<button id="joms-js--attachment-uploader-button">').appendTo( container );
            uploader  = new window.plupload.Uploader({
                url: joms.BASE_URL + 'index.php?option=com_community&view=photos&task=ajaxPreviewComment',
                container: 'joms-js--attachment-uploader',
                browse_button: 'joms-js--attachment-uploader-button',
                runtimes: 'html5,html4',
                multi_selection: false
            });

            uploader.bind( 'FilesAdded', addAttachmentAdded );
            uploader.bind( 'BeforeUpload', addAttachmentBeforeUpload );
            uploader.bind( 'Error', addAttachmentError );
            uploader.bind( 'FileUploaded', addAttachmentUploaded );
            uploader.init();

            uploaderAttachment = elem && elem.find('.joms-textarea__attachment');
            uploaderButton = container.find('input[type=file]');
            callback();
        });
    });
}

function addAttachmentAdded( up ) {
    uploaderError = false;

    window.setTimeout(function() {
        var ct      = uploaderAttachment,
            loading = ct.find('.joms-textarea__attachment--loading'),
            thumb   = ct.find('.joms-textarea__attachment--thumbnail'),
            button  = ct.find('button');

        if ( uploaderError ) {
            return;
        }

        up.start();
        up.refresh();

        thumb.find('img').replaceWith('<img>');
        thumb.hide();
        button.hide();
        loading.show();
        ct.show();

        // Disable sibling items.
        uploaderRef.siblings('svg')
            .data('disabled', 1)
            .css('opacity', 0.5);

    }, 0);
}

function addAttachmentBeforeUpload( up ) {
    var params = '',
        prop;

    for ( prop in uploaderParams ) {
        params += '&' + prop + '=' + uploaderParams[prop];
    }

    up.settings.url += params;
}

function addAttachmentError( up, error ) {
    uploaderError = true;
    window.alert( error && error.message || 'Undefined error.' );
}

function addAttachmentUploaded( up, file, info ) {
    var json, ct, loading, thumb, button, img, label;

    try {
        json = JSON.parse( info.response );
    } catch ( e ) {}

    json || (json = {});

    ct = uploaderAttachment;

    if ( json.error || json.msg ) {
        window.alert( json.error || json.msg );
        ct.hide();
        return;
    }

    if ( ! ( ( json.thumb_url && json.photo_id ) || json.id ) ) {
        window.alert( 'Undefined error.' );
        ct.hide();
        return;
    }

    loading = ct.find('.joms-textarea__attachment--loading');
    thumb   = ct.find('.joms-textarea__attachment--thumbnail');
    img     = thumb.find('img');
    button  = ct.find('button');

    if ( uploaderType === 'file' ) {
        label = $( '<b>' + file.name + '</b>' ).data( {'id': json.id, 'path': json.path, 'name' : file.name} );
        img.removeData( 'photo_id' );
        img.hide().siblings('b').remove();
        img.after( label );
    } else {
        img.siblings('b').remove();
        img.attr( 'src', json.thumb_url );
        img.data( 'photo_id', json.photo_id ).show();
    }

    loading.hide();
    thumb.show();
    button.show();
    ct.show();
}

function removeAttachment( elem ) {
    elem = $( elem );
    elem = elem.closest('.joms-textarea__attachment');

    if ( elem ) {
        elem.find('.joms-textarea__attachment--thumbnail img').replaceWith('<img src="" alt="attachment">');
        elem.hide();
        elem.removeData('no_thumb');
        elem.removeAttr('data-no_thumb');
    }

    // Enable sibling items.
    if ( uploaderRef ) {
        uploaderRef.siblings('svg')
            .removeData('disabled')
            .css('opacity', '');
    }
}

function cancel( id ) {
    var $ct, $reply, data;

    if ( id && id.nodeType ) {
        $ct = $( id ).closest('.joms-js--comment');
    } else {
        $ct = $( '.joms-js--comment-' + id );
    }

    if ( !$ct.length ) {
        return;
    }

    data = $ct.data();
    $reply = $( '.joms-js--newcomment-' + data.parent );

    $ct.find('.joms-js--comment-editor').hide();
    $ct.find('.joms-js--comment-body').show();
    $ct.find('.joms-js--comment-actions').show();
    $reply.show();
}

function toggleText( id ) {
    var $text = $( '.joms-js--comment-text-' + id ),
        $full = $( '.joms-js--comment-textfull-' + id ),
        $btn  = $( '.joms-js--comment-texttoggle-' + id );

    if ( $full.is(':visible') ) {
        $full.hide();
        $text.show();
        $btn.html( $btn.data('lang-more') );
    } else {
        $text.hide();
        $full.show();
        $btn.html( $btn.data('lang-less') );
    }
}

function _onInboxAdded( html ) {
    var ct, status, reply, textarea, tagging, attachment, loc, use, href, i;

    ct = $('.joms-js--inbox');
    status = $('.joms-js--inbox-status');
    reply = $('.joms-js--inbox-reply');
    textarea = reply.find('textarea');
    attachment = reply.find('.joms-textarea__attachment');

    html = $( $.trim( html ) );
    loc  = window.location.href.split('#')[0];
    use  = html.find('use');

    for ( i = 0; i < use.length; i++ ) {
        href = use.eq(i).attr('xlink:href').split('#')[1];
        href = loc + '#' + href;
        use.eq(i).attr( 'xlink:href', href );
    }

    ct.append( html );

    if ( textarea.length ) {
        tagging = textarea.data('joms-tagging');
        tagging ? tagging.clear() : textarea.val('');
    }

    if ( attachment.length ) {
        attachment.hide();
    }

    // update seen state
    if ( status.length ) {
        status.html('<span>' + status.data('lang-notseen') + '</span>');
    }

    initVideoPlayers();
}

function _onInboxUpdated( id, html ) {
    var item, loc, use, href;

    if ( !html ) {
        _onInboxRemoved( id );
        return;
    }

    item = $('.joms-js--inbox-item-' + id);

    html = $( $.trim( html ) );
    loc  = window.location.href.split('#')[0];
    use  = html.find('use');
    href = use.attr('xlink:href').split('#')[1];
    href = loc + '#' + href;

    use.attr( 'xlink:href', href );
    item.replaceWith( html );

    initVideoPlayers();
}

function _onInboxRemoved( id ) {
    var item;
    item = $('.joms-js--inbox-item-' + id);
    item.fadeOut( 500, function () {
        item.remove();
    });
}

function showEmoticonBoard(elm) {
    var $body = $('body'),
        $board = $('.joms-emoticon-js__board'),
        $icon = $(elm).parents('.joms-icon--emoticon'),
        offset = $(elm).offset(),
        offsetTop = 0,
        emoticons = joms.getData('joms_emo'),
        isRTL = $('html').attr('dir') === 'rtl';

    if (!$board.length) {
        html = renderEmoticonBoard(emoticons);
        $body.append(html);
        $board = $('.joms-emoticon-js__board');
    }

    var spacer = isRTL ? 15 : ($board.outerWidth() - 30);
    var above = {
        display: 'block',
        top: (offset.top - $board.outerHeight()) +'px',
        left: (offset.left - spacer) + 'px',
        position: 'absolute'
    }

    var animate_above = {
        opacity: '1',
        top: (offset.top - $board.outerHeight() - 10) + 'px'
    }

    var below = {
        display: 'block',
        top: (offset.top + 20) +'px',
        left: (offset.left - spacer) + 'px',
        position: 'absolute'
    }

    var animate_below = {
        opacity: '1',
        top: (offset.top + 24) + 'px'
    }

    offsetTop = offset.top - $(window).scrollTop();
    var pos, ani, positionClass;

    if (offsetTop > ($board.outerHeight() + 30)) {
        pos = above;
        ani = animate_above;
        positionClass = 'joms-board--above'
    } else {
        pos = below;
        ani = animate_below;
        positionClass = 'joms-board--below';
    }

    $board.is(':hidden') && setTimeout(function() {
        $('.joms-icon--active').removeClass('joms-icon--active');
        $icon.addClass('joms-icon--active');
        $board.css(pos);
        $board.addClass(positionClass)
        setTimeout(function() {
            $board.css(ani);
        }, 100);

        $(document).one('click', function() {
            $board.css({
                display: 'none',
                opacity: '0'
            });

            $board.removeClass('joms-board--above joms-board--below');
        });
    }, 100)
}

function renderEmoticonBoard(emoticons) {
    var html = '<ul class="joms-emoticon__board joms-emoticon-js__board">';
    for ( var key in emoticons ) {
        var emo = emoticons[key];
        html += '\
        <li>\
            <span\
                title="'+key+'"\
                onclick="joms.view.comment.insertEmoticon(this)"\
                class="joms-emo2 joms-emo2-'+key+'"\
                data-code="'+emo[0]+'" >\
            </span>\
        </li>\
        ';
    }
    
    html += '</ul>';

    return html;
}

function insertEmoticon(elm) {
    var $icon = $('.joms-icon--active'),
        $wrapper = $icon.parent().find('.joms-textarea__wrapper'),
        $input = $wrapper.find('textarea.joms-textarea'),
        $hiddenInput = $wrapper.find('input.joms-textarea__hidden'),
        code = $(elm).attr('data-code'),
        value,
        start = $input.prop("selectionStart");
    
    value = $input.val().slice(0, start) + code + $input.val().slice(start);

    $hiddenInput.val(value);
    $input.val(value);
    $input.prop("selectionStart", start + code.length);
    $input.prop("selectionEnd", start + code.length);
    $input.focus();
    $input.trigger('keydown')
}

// Exports.
return {
    start: initialize,
    stop: uninitialize,
    like: like,
    unlike: unlike,
    edit: edit,

    cancel: cancel,

    remove: remove,
    removeTag: removeTag,
    removePreview: removePreview,
    removeThumbnail: removeThumbnail,

    addAttachment: addAttachment,
    removeAttachment: removeAttachment,

    toggleText: toggleText,

    initInputbox: initInputbox,

    showEmoticonBoard: showEmoticonBoard,
    insertEmoticon: insertEmoticon
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.stream || (joms.popup.stream = {});
    joms.popup.stream.remove = factory( root );

    define('popups/stream.remove',[ 'utils/popup' ], function() {
        return joms.popup.stream.remove;
    });

})( window, function() {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'activities,ajaxConfirmDeleteActivity',
        data: [ '', id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '[data-ui-object=popup-button-cancel]', cancel );
            elem.on( 'click', '[data-ui-object=popup-button-save]', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    joms.ajax({
        func: 'activities,ajaxDeleteActivity',
        data: [ '', id ],
        callback: function( json ) {
            var item;

            elem.off();
            popup.close();

            if ( json.success ) {
                item = joms.jQuery('.joms-stream').filter('[data-stream-id=' + id + ']');
                item.fadeOut( 500, function() {
                    item.remove();
                });
            }
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div>',
            '<div class="joms-popup__content">', ( json.error || json.message ), '</div>',
            '<div class="joms-popup__action">',
            '<a href="javascript:" class="joms-button--neutral joms-button--small joms-left" data-ui-object="popup-button-cancel">', json.btnCancel, '</a> &nbsp;',
            '<button class="joms-button--primary joms-button--small" data-ui-object="popup-button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.stream || (joms.popup.stream = {});
    joms.popup.stream.editLocation = factory( root, $ );

    define('popups/stream.editlocation',[ 'utils/popup' ], function() {
        return joms.popup.stream.editLocation;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'activities,ajaxeditLocation',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '[data-ui-object=popup-button-cancel]', cancel );
            elem.on( 'click', '[data-ui-object=popup-button-save]', save );

            _.defer(function() {
                initMap( json );
            });
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    var item = elem.find('.joms-js--location-input'),
        name = item.data('name'),
        lat  = item.data('lat'),
        lng  = item.data('lng');

    joms.ajax({
        func: 'activities,ajaxSaveLocation',
        data: [ id, name, lat, lng ],
        callback: function( json ) {
            var stream;

            elem.off();
            popup.close();

            if ( json.success ) {
                stream = $('.joms-stream').filter('[data-stream-id=' + id + ']');
                stream.find('.joms-status-location a').html( name );
            }
        }
    });
}

function initMap( json ) {
    joms.util.map(function() {
        var map = elem.find('.joms-js--location-map'),
            input = elem.find('.joms-js--location-input'),
            loading = elem.find('.joms-js--location-loading'),
            selector = elem.find('.joms-js--location-selector'),
            position, options, map, marker;

        position = new window.google.maps.LatLng( json.latitude, json.longitude );

        options = {
            center: position,
            zoom: 14,
            mapTypeId: window.google.maps.MapTypeId.ROADMAD,
            mapTypeControl: false,
            disableDefaultUI: true,
            draggable: false,
            scaleControl: false,
            scrollwheel: false,
            navigationControl: false,
            streetViewControl: false,
            disableDoubleClickZoom: true
        };

        map = new window.google.maps.Map( map[0], options );
        marker = new window.google.maps.Marker({
            draggable: false,
            map: map
        });

        marker.setPosition( position );
        map.panTo( position );

        input.on( 'input', function() {
            selector.hide()
            loading.show();
            onInput( this.value );
        });

        var onInput = _.debounce(function( keyword ) {
            keyword = $.trim( keyword );
            if ( ! keyword ) {
                return;
            }

            joms.util.map.search( keyword ).done(function( data ) {
                var html = '';
                if ( _.isArray( data ) ) {
                    _.each( data, function( item ) {
                        html += '<a class="joms-map--location-item" data-id="' + item.id + '"">'
                            + '<strong>' + item.name + '</strong><br />'
                            + '<span>' + ( item.description || '' ) + '</span>'
                            + '</a>';
                    });
                }

                loading.hide();
                selector.html( html );
                selector.show();
            });
        }, 1000 );

        input.val( json.location );
        onInput( json.location );

        selector.on( 'click', '.joms-map--location-item', function() {
            var elem = $( this ),
                data = elem.data(),
                name = elem.find( 'strong' ).text(),
                position;

            input.data( 'name', name );
            joms.util.map.detail( data.id ).done(function( place ) {
                var geometry = place.geometry,
                    location = geometry.location,
                    viewport = geometry.viewport;

                input.data( 'lat', location.lat() );
                input.data( 'lng', location.lng() );

                map.setCenter( location );
                marker.setPosition( location );
                if ( viewport ) {
                    map.fitBounds( viewport );
                } else {
                    map.setZoom( 15 );
                }
            });
        });

    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div>',
            '<div class="joms-popup__content">', ( json.html || json.error ), '</div>',
            '<div class="joms-popup__action">',
            '<a href="javascript:" class="joms-button--neutral joms-button--small joms-left" data-ui-object="popup-button-cancel">', json.btnCancel, '</a> &nbsp;',
            '<button class="joms-button--primary joms-button--small" data-ui-object="popup-button-save">', json.btnEdit, '</button>',
            '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.stream || (joms.popup.stream = {});
    joms.popup.stream.addFeatured = factory( root );

    define('popups/stream.addfeatured',[ 'utils/popup' ], function() {
        return joms.popup.stream.addFeatured;
    });

})( window, function( window ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    var context = window.joms_page;
    var contextid;

    if ( context === 'profile' ) {
        context = 'profile';
        contextid = window.joms_user_id;
    } else if ( context === 'groups' ) {
        context = 'group';
        contextid = window.joms_group_id;
    } else if ( context === 'events' ) {
        context = 'event';
        contextid = window.joms_event_id;
    } else {
        context = 'frontpage';
        contextid = 0;
    }

    joms.ajax({
        func: 'system,ajaxFeatureStream',
        data: [ context, contextid, id ],
        callback: function( json ) {
            if ( json.success ) {
                window.location.reload();
                return;
            }

            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', (json.title || '&nbsp;'), '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.error || json.message ), '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.stream || (joms.popup.stream = {});
    joms.popup.stream.removeFeatured = factory( root, $ );

    define('popups/stream.removefeatured',[ 'utils/popup' ], function() {
        return joms.popup.stream.removeFeatured;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    var context = window.joms_page;
    var contextid;

    if ( context === 'profile' ) {
        context = 'profile';
        contextid = window.joms_user_id;
    } else if ( context === 'groups' ) {
        context = 'group';
        contextid = window.joms_group_id;
    } else if ( context === 'events' ) {
        context = 'event';
        contextid = window.joms_event_id;
    } else {
        context = 'frontpage';
        contextid = 0;
    }

    joms.ajax({
        func: 'system,ajaxUnfeatureStream',
        data: [ context, contextid, id ],
        callback: function( json ) {
            if ( json.success ) {
                window.location.reload();
                return;
            }

            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', (json.title || '&nbsp;'), '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.error || json.message ), '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.stream || (joms.popup.stream = {});
    joms.popup.stream.removeLocation = factory( root );

    define('popups/stream.removelocation',[ 'utils/popup' ], function() {
        return joms.popup.stream.removeLocation;
    });

})( window, function() {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'activities,ajaxRemoveLocation',
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '[data-ui-object=popup-button-cancel]', cancel );
            elem.on( 'click', '[data-ui-object=popup-button-save]', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    joms.ajax({
        func: 'activities,deleteLocation',
        data: [ id ],
        callback: function( json ) {
            var item;

            elem.off();
            popup.close();

            if ( json.success ) {
                item = joms.jQuery('.joms-stream').filter('[data-stream-id=' + id + ']');
                item.find('.joms-status-location').remove();
            }
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div>',
            '<div class="joms-popup__content">', ( json.error || json.message ), '</div>',
            '<div class="joms-popup__action">',
            '<a href="javascript:" class="joms-button--neutral joms-button--small joms-left" data-ui-object="popup-button-cancel">', json.btnNo, '</a> &nbsp;',
            '<button class="joms-button--primary joms-button--small" data-ui-object="popup-button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.stream || (joms.popup.stream = {});
    joms.popup.stream.removeMood = factory( root );

    define('popups/stream.removemood',[ 'utils/popup' ], function() {
        return joms.popup.stream.removeMood;
    });

})( window, function() {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'activities,ajaxConfirmRemoveMood',
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '[data-ui-object=popup-button-cancel]', cancel );
            elem.on( 'click', '[data-ui-object=popup-button-save]', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    joms.ajax({
        func: 'activities,ajaxRemoveMood',
        data: [ id ],
        callback: function( json ) {
            var item;

            elem.off();
            popup.close();

            if ( json.success ) {
                item = joms.jQuery('.joms-stream').filter('[data-stream-id=' + id + ']');
                item.find('[data-type=stream-content]').find('span').eq(0).html( json.html );
            }
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div>',
            '<div class="joms-popup__content">', ( json.error || json.message ), '</div>',
            '<div class="joms-popup__action">',
            '<a href="javascript:" class="joms-button--neutral joms-button--small joms-left" data-ui-object="popup-button-cancel">', json.btnNo, '</a> &nbsp;',
            '<button class="joms-button--primary joms-button--small" data-ui-object="popup-button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.stream || (joms.popup.stream = {});
    joms.popup.stream.report = factory( root );

    define('popups/stream.report',[ 'utils/popup' ], function() {
        return joms.popup.stream.report;
    });

})( window, function() {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'system,ajaxReport',
        data: [],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'change', 'select', changeText );
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function changeText( e ) {
    elem.find('textarea').val( e.target.value );
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    var rTrim = /^\s+|\s+$/g,
        message;

    message = elem.find('textarea').val();
    message = message.replace( rTrim, '' );

    if ( !message ) {
        elem.find('.joms-js--error').show();
        return;
    }

    elem.find('.joms-js--error').hide();

    joms.ajax({
        func: 'system,ajaxSendReport',
        data: [ 'activities,reportActivities', window.location.href, message, id ],
        callback: function( json ) {
            elem.find('.joms-js--step1').hide();
            elem.find('.joms-js--step2').show().children().html( json.error || json.message );
        }
    });
}


function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock joms-popup--500">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1', ( json.error ? ' joms-popup__hide' : '' ), '">',
            json.html,
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnCancel, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnSend, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-js--step2', ( json.error ? '' : ' joms-popup__hide' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', ( json.error || '' ), '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.stream || (joms.popup.stream = {});
    joms.popup.stream.selectPrivacy = factory( root, $ );

    define('popups/stream.selectprivacy',[ 'utils/popup' ], function() {
        return joms.popup.stream.selectPrivacy;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    popup.items[0] = {
        type: 'inline',
        src: buildHtml()
    };

    popup.updateItemHTML();

    elem = popup.contentContainer;
    elem.on( 'click', 'a', save );
}

function save( e ) {
    var el = $( e.currentTarget ),
        privacy = el.data('value'),
        className = el.data('classname');

    joms.ajax({
        func: 'activities,ajaxUpdatePrivacyActivity',
        data: [ id, privacy ],
        callback: function( json ) {
            var item;

            elem.off();
            popup.close();

            if ( json.success ) {
                item = $('.joms-stream').filter('[data-stream-id=' + id + ']');
                item.find('.joms-stream__meta use').attr( 'xlink:href', window.location + '#' + className );
            }
        }
    });
}

function buildHtml() {
    var privacies, filter, base, html, i;

    privacies = [
        [ 'public', 10, window.joms_lang.COM_COMMUNITY_PRIVACY_PUBLIC, 'earth' ],
        [ 'site_members', 20, window.joms_lang.COM_COMMUNITY_PRIVACY_SITE_MEMBERS, 'users' ],
        [ 'friends', 30, window.joms_lang.COM_COMMUNITY_PRIVACY_FRIENDS, 'user' ],
        [ 'me', 40, window.joms_lang.COM_COMMUNITY_PRIVACY_ME, 'lock' ]
    ];

    // Filter.
    filter = window.joms_privacylist;
    if ( filter && filter.length ) {
        for ( i = privacies.length - 1; i >= 0; i-- ) {
            if ( filter.indexOf( privacies[i][0] ) < 0 ) {
                privacies.splice( i, 1 );
            }
        }
    }

    base = window.location.href;
    base = base.replace( /#.*$/, '' );

    html = '';
    for ( i = 0; i < privacies.length; i++ ) {
        html += '<a href="javascript:" data-value="' + privacies[i][1] + '" data-classname="joms-icon-' + privacies[i][3] + '">';
        html += '<svg viewBox="0 0 16 16" class="joms-icon"><use xlink:href="' + base + '#joms-icon-' + privacies[i][3] + '"></use></svg> ';
        html += '<span>' + privacies[i][2] + '</span></a>';
    }

    return [
        '<div class="joms-popup joms-popup--whiteblock joms-popup--privacy">',
        '<div><div class="joms-popup__content joms-popup__content--single">', html, '</div></div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.stream || (joms.popup.stream = {});
    joms.popup.stream.share = factory( root, $ );

    define('popups/stream.share',[ 'utils/loadlib', 'utils/popup' ], function() {
        return joms.popup.stream.share;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'activities,ajaxSharePopup',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            initPhotoArranger();
            initVideoPlayers();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    var attachment = {
        msg: elem.find('textarea.joms-textarea').val(),
        privacy: elem.find('[data-ui-object=joms-dropdown-value]').val()
    };

    joms.ajax({
        func: 'activities,ajaxAddShare',
        data: [ id, JSON.stringify( attachment ) ],
        callback: function( json ) {
            elem.off();
            popup.close();

            if ( json.success ) {
                $('.joms-stream__container').prepend( json.html );
                initPhotoArranger();
                initVideoPlayers();
            }
        }
    });
}

function initPhotoArranger() {
    var initialized = '.joms-js--initialized',
        $containers = $('.joms-media--images').not( initialized );

    $containers.each(function() {
        var $ct = $( this ),
            $imgs = $ct.find('img'),
            counter = 0;

        $imgs.each(function() {
            var $img = $( this );

            $('<img>').load(function() {
                counter++;
                if ( counter === $imgs.length ) {
                    $ct.siblings('.joms-media--loading').remove();
                    $ct.addClass( initialized.substr(1) );
                    $imgs.show();
                    joms.util.photos.arrange( $ct );
                }

            }).attr( 'src', $img.attr('src') );
        });
    });
}

function initVideoPlayers() {
    var initialized = '.joms-js--initialized',
        cssVideos = '.joms-js--video',
        videos = $( cssVideos ).not( initialized ).addClass( initialized.substr(1) );

    if ( !videos.length ) {
        return;
    }

    joms.loadCSS( joms.ASSETS_URL + 'vendors/mediaelement/mediaelementplayer.min.css' );
    videos.on( 'click.joms-video', cssVideos + '-play', function() {
        var $el = $( this ).closest( cssVideos );
        joms.util.video.play( $el, $el.data() );
    });

    if ( joms.ios ) {
        setTimeout(function() {
            videos.find( cssVideos + '-play' ).click();
        }, 2000 );
    }
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock joms-popup--500">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div>',
            '<div class="joms-popup__content joms-popup__content--single">', ( json.html || '' ), '</div>',
            '<div class="joms-popup__action">',
                '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnCancel, '</button> &nbsp;',
                '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnShare, '</button> &nbsp;',
                '<div style="display:inline-block; position:relative;">',
                    '<div class="joms-button--privacy" data-ui-object="joms-dropdown-button" data-name="share-privacy">',
                        '<svg class="joms-icon" viewBox="0 0 16 16"><use xlink:href="#joms-icon-earth"></use></svg>',
                        '<input type="hidden" data-ui-object="joms-dropdown-value" value="10">',
                    '</div>',
                    '<ul class="joms-dropdown joms-dropdown--privacy" data-name="share-privacy">',
                        '<li data-classname="joms-icon-earth" data-value="10" style="white-space:nowrap">',
                            '<svg class="joms-icon" viewBox="0 0 16 16"><use xlink:href="#joms-icon-earth"></use></svg>',
                            ' <span>', window.joms_lang.COM_COMMUNITY_PRIVACY_PUBLIC, '</span>',
                        '</li>',
                        '<li data-classname="joms-icon-users" data-value="20" style="white-space:nowrap">',
                            '<svg class="joms-icon" viewBox="0 0 16 16"><use xlink:href="#joms-icon-users"></use></svg>',
                            ' <span>', window.joms_lang.COM_COMMUNITY_PRIVACY_SITE_MEMBERS, '</span>',
                        '</li>',
                        '<li data-classname="joms-icon-user" data-value="30" style="white-space:nowrap">',
                            '<svg class="joms-icon" viewBox="0 0 16 16"><use xlink:href="#joms-icon-user"></use></svg>',
                            ' <span>', window.joms_lang.COM_COMMUNITY_PRIVACY_FRIENDS, '</span>',
                        '</li>',
                        '<li data-classname="joms-icon-lock" data-value="40" style="white-space:nowrap">',
                            '<svg class="joms-icon" viewBox="0 0 16 16"><use xlink:href="#joms-icon-lock"></use></svg>',
                            ' <span>', window.joms_lang.COM_COMMUNITY_PRIVACY_ME, '</span>',
                        '</li>',
                    '</ul>',
                '</div>',
            '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.stream || (joms.popup.stream = {});
    joms.popup.stream.showComments = factory( root, $ );

    define('popups/stream.showcomments',[ 'utils/popup' ], function() {
        return joms.popup.stream.showComments;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, id, type;

function render( _popup, _id, _type ) {
    var data;

    if ( elem ) elem.off();
    popup = _popup;
    id = _id;
    type = _type;

    data = [ id ];
    if ( type ) {
        data.push( type );
    }

    joms.ajax({
        func: 'system,ajaxStreamShowComments',
        data: data,
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            initVideoPlayers();
        }
    });
}

function initVideoPlayers() {
    var initialized = '.joms-js--initialized',
        cssVideos = '.joms-js--video',
        videos = $('.joms-comment__body,.joms-js--inbox').find( cssVideos ).not( initialized ).addClass( initialized.substr(1) );

    if ( !videos.length ) {
        return;
    }

    joms.loadCSS( joms.ASSETS_URL + 'vendors/mediaelement/mediaelementplayer.min.css' );
    videos.on( 'click.joms-video', cssVideos + '-play', function() {
        var $el = $( this ).closest( cssVideos );
        joms.util.video.play( $el, $el.data() );
    });

    if ( joms.ios ) {
        setTimeout(function() {
            videos.find( cssVideos + '-play' ).click();
        }, 2000 );
    }
}

function buildHtml( json ) {
    var isEmpty = true,
        fragment;

    json || (json = {});

    fragment = $( $.trim( json.html || '' ) );
    if ( fragment.children().length ) {
        isEmpty = false;
    }

    return [
        '<div class="joms-popup joms-popup--whiteblock joms-popup--rounded joms-popup--80pc">',
        '<button class="mfp-close joms-hide"></button>',
        '<div class="joms-comment">', ( isEmpty ? window.joms_lang.COM_COMMUNITY_NO_COMMENTS_YET : json.html ), '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id, type ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id, type );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.stream || (joms.popup.stream = {});
    joms.popup.stream.showLikes = factory( root, $ );

    define('popups/stream.showlikes',[ 'utils/popup' ], function() {
        return joms.popup.stream.showLikes;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, id, target;

function render( _popup, _id, _target ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;
    target = _target;

    joms.ajax({
        func: 'system,ajaxStreamShowLikes',
        data: [ id, target ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();
        }
    });
}

function buildHtml( json ) {
    var isEmpty = true,
        fragment;

    json || (json = {});

    fragment = $( $.trim( json.html || '' ) );
    if ( fragment.children().length ) {
        isEmpty = false;
    }

    return [
        '<div class="joms-popup joms-popup--whiteblock joms-popup--rounded joms-popup--80pc">',
        '<button class="mfp-close joms-hide"></button>',
        '<div class="joms-comment">', ( isEmpty ? window.joms_lang.COM_COMMUNITY_NO_LIKES_YET : json.html ), '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id, target ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id, target );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.stream || (joms.popup.stream = {});
    joms.popup.stream.showOthers = factory( root, $ );

    define('popups/stream.showothers',[ 'utils/popup' ], function() {
        return joms.popup.stream.showOthers;
    });

})( window, joms.jQuery, function() {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'activities,ajaxShowOthers',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock joms-popup--rounded joms-popup--80pc">',
        '<button class="mfp-close joms-hide"></button>',
        '<div class="joms-comment">', ( json.html || '' ), '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.stream = factory( root, joms.popup.stream || {});

    define('popups/stream',[
        'popups/stream.remove',
        'popups/stream.editlocation',
        'popups/stream.addfeatured',
        'popups/stream.removefeatured',
        'popups/stream.removelocation',
        'popups/stream.removemood',
        'popups/stream.report',
        'popups/stream.selectprivacy',
        'popups/stream.share',
        'popups/stream.showcomments',
        'popups/stream.showlikes',
        'popups/stream.showothers'
    ], function() {
        return joms.popup.stream;
    });

})( window, function( window, sub ) {

// Exports.
return joms._.extend({}, sub );

});

(function( root, $, factory ) {

    joms.view || (joms.view = {});
    joms.view.customize = factory( root, $ );

    define('views/customize',[ 'popups/stream' ], function() {
        return joms.view.stream;
    });

})( window, joms.jQuery, function(/* window, $ */) {

function initialize() {
    uninitialize();
}

function uninitialize() {
}

// Exports.
return {
    start: initialize,
    stop: uninitialize
};

});

(function( root, $, factory ) {

    joms.view || (joms.view = {});
    joms.view.misc = factory( root, $ );

    define('views/misc',[],function() {
        return joms.view.misc;
    });

})( window, joms.jQuery, function( window, $ ) {

var $main, $sidebar;

function initialize() {
    $main = $('.joms-main');
    $sidebar = $('.joms-sidebar');

    rearrangeModuleDiv();
    $( window ).on( 'resize', rearrangeModuleDiv );
}

var rearrangeModuleDiv = joms._.debounce(function() {
    if ( joms.screenSize() !== 'large' ) {
        if ( $sidebar.nextAll('.joms-main').length ) {
            $sidebar.insertAfter( $main );
        }
    } else {
        if ( $sidebar.prevAll('.joms-main').length ) {
            $sidebar.insertBefore( $main );
        }
    }
}, 500 );

var fixSVG = joms._.debounce(function() {
    var url = window.joms_current_url,
        svgFixClass = 'joms-icon--svg-fixed',
        svg;

    if ( !url ) {
        return;
    }

    svg = $('.joms-icon use').not('.' + svgFixClass );
    svg.each(function() {
        var href = ( this.getAttribute('xlink:href') || '' ),
            path = href.replace( /^[^#]*#/, '#' );

        if ( href === url + path ) {
            svgFixClass += ' joms-icon--svg-unmodified';
        } else {
            this.setAttribute( 'xlink:href', url + path );
        }

        this.setAttribute( 'class', svgFixClass );
    });
}, 200 );

// Exports.
return {
    start: initialize,
    fixSVG: fixSVG
};

});

(function( root, $, factory ) {

    joms.view || (joms.view = {});
    joms.view.stream = factory( root, $ );

    define('views/stream',[ 'popups/stream' ], function() {
        return joms.view.stream;
    });

})( window, joms.jQuery, function( window, $ ) {

var container;

function initialize() {
    uninitialize();
    container = $('.joms-stream__wrapper');
}

function uninitialize() {
    if ( container ) {
        container.off();
    }
}

function like( id ) {
    var item = container.find( '.joms-js--stream-' + id );
    if (+item.attr('do-like')) {
        return;
    }
    item.attr('do-like', 1);
    joms.ajax({
        func: 'system,ajaxStreamAddLike',
        data: [ id ],
        callback: function( json ) {
            var item, btn, info, counter, status;

            if ( json.success ) {
                item = container.find( '.joms-js--stream-' + id );
                if ( item.length ) {
                    btn = item.find('.joms-stream__actions').find('.joms-button--liked');
                    btn.attr( 'onclick', 'joms.api.streamUnlike(\'' + id + '\');' );
                    btn.addClass('liked');
                    btn.find('span').html( btn.data('lang-unlike') );
                    btn.find('use').attr( 'xlink:href', window.location + '#joms-icon-thumbs-down' );

                    info = item.find('.joms-stream__status');
                    if ( !json.html ) {
                        info.remove();
                    } else if ( info.length ) {
                        info.html( json.html );
                    } else {
                        info = item.find('.joms-stream__actions');
                        info = $('<div class=joms-stream__status />').insertAfter( info );
                        info.html( json.html );
                    }

                    status = item.find('.joms-stream__status--mobile');
                    if ( status.length ) {
                        counter = status.find( '.joms-like__counter--' + id );
                        counter.html( +counter.eq(0).text() + 1 );
                        status.find('.joms-like__status').show();
                    }
                    item.attr('do-like', 0);
                }
            }
        }
    });
}

function unlike( id ) {
    var item = container.find( '.joms-js--stream-' + id );
    if (+item.attr('do-like')) {
        return;
    }
    item.attr('do-like', 1);
    joms.ajax({
        func: 'system,ajaxStreamUnlike',
        data: [ id ],
        callback: function( json ) {
            var item, btn, info, counter, status;

            if ( json.success ) {
                item = container.find( '.joms-js--stream-' + id );
                if ( item.length ) {
                    btn = item.find('.joms-stream__actions').find('.joms-button--liked');
                    btn.attr( 'onclick', 'joms.api.streamLike(\'' + id + '\');' );
                    btn.removeClass('liked');
                    btn.find('span').html( btn.data('lang-like') );
                    btn.find('use').attr( 'xlink:href', window.location + '#joms-icon-thumbs-up' );

                    info = item.find('.joms-stream__status');
                    if ( !json.html ) {
                        info.remove();
                    } else if ( info.length ) {
                        info.html( json.html );
                    } else {
                        info = item.find('.joms-stream__actions');
                        info = $('<div class=joms-stream__status />').insertAfter( info );
                        info.html( json.html );
                    }

                    status = item.find('.joms-stream__status--mobile');
                    if ( status.length ) {
                        counter = status.find( '.joms-like__counter--' + id );
                        var val = +counter.eq(0).text() - 1;
                        counter.html( val );
                        if (val === 0) {
                            status.find('.joms-like__status').hide();
                        }
                    }
                    item.attr('do-like', 0);
                }
            }
        }
    });
}

function edit( id ) {
    var $stream   = $( '.joms-js--stream-' + id ).eq(0),
        $sbody    = $stream.find('.joms-stream__body'),
        $scontent = $sbody.find('[data-type=stream-content]'),
        $seditor  = $sbody.find('[data-type=stream-editor]'),
        $textarea = $seditor.find('textarea'),
        origValue = $textarea.val();

    $scontent.hide();
    $seditor.show();
    $textarea.removeData('joms-tagging');
    $textarea.jomsTagging();
    $textarea.off( 'reset.joms-tagging' );
    $textarea.on( 'reset.joms-tagging', function() {
        $seditor.hide();
        $scontent.show();
        $textarea.val( origValue );
    });

    $textarea.focus();
}

function editSave( id, text, origText ) {
    joms.ajax({
        func: 'activities,ajaxSaveStatus',
        data: [ id, text ],
        callback: function( json ) {
            var $stream   = $('.joms-stream').filter('[data-stream-id=' + id + ']'),
                $sbody    = $stream.find('.joms-stream__body'),
                $scontent = $sbody.find('[data-type=stream-content]'),
                $seditor  = $sbody.find('[data-type=stream-editor]'),
                $textarea = $seditor.find('textarea');

            if ( json.success ) {
                $scontent.html( '<span>' + json.data + '</span>' );
                $textarea.val( json.unparsed );
            } else {
                $textarea.val( origText );
            }

            $seditor.hide();
            $scontent.show();
        }
    });
}

function save( id, el ) {
    var $stream   = $( el ).closest('.joms-js--stream'),
        $sbody    = $stream.find('.joms-stream__body'),
        $seditor  = $sbody.find('[data-type=stream-editor]'),
        $textarea = $seditor.find('textarea'),
        value     = $textarea.val();

    if ($textarea[0].joms_hidden) {
        value = $textarea[0].joms_hidden.val();
    }

    editSave( id, value, value );
}

function cancel( id ) {
    var $stream   = $( '.joms-js--stream-' + id ),
        $sbody    = $stream.find('.joms-stream__body'),
        $scontent = $sbody.find('[data-type=stream-content]'),
        $seditor  = $sbody.find('[data-type=stream-editor]');

    $seditor.hide();
    $scontent.show();
}

function editLocation( id ) {
    joms.popup.stream.editLocation( id );
}

function remove( id ) {
    joms.popup.stream.remove( id );
}

function removeLocation( id ) {
    joms.popup.stream.removeLocation( id );
}

function removeMood( id ) {
    joms.popup.stream.removeMood( id );
}

function removeTag( id ) {
    joms.ajax({
        func: 'activities,ajaxRemoveUserTag',
        data: [ id, 'post' ],
        callback: function( json ) {
            var $stream, $sbody, $soptions, $scontent, $seditor, $textarea;

            if ( json.success ) {
                $stream   = $( '.joms-js--stream-' + id );
                $sbody    = $stream.find('.joms-stream__body');
                $soptions = $stream.find('.joms-list__options').find('.joms-dropdown').find('.joms-js--contextmenu-removetag');
                $scontent = $sbody.find('[data-type=stream-content]');
                $seditor  = $sbody.find('[data-type=stream-editor]');
                $textarea = $seditor.find('textarea');

                $scontent.html( '<span>' + json.data + '</span>' );
                $textarea.val( json.unparsed );
                $soptions.remove();
            }
        }
    });
}

function selectPrivacy( id ) {
    joms.popup.stream.selectPrivacy( id );
}

function share( id ) {
    joms.popup.stream.share( id );
}

function hide( streamId, userId ) {
    joms.ajax({
        func: 'activities,ajaxHideStatus',
        data: [ streamId, userId ],
        callback: function( json ) {
            var streams;

            if ( json.success ) {
                streams = container.find('.joms-stream[data-stream-id=' + streamId + ']');
                streams.fadeOut( 500, function() {
                    streams.remove();
                });
            }
        }
    });
}


function ignoreUser( id ) {
    joms.popup.stream.ignoreUser( id );
}

function showLikes( id, target ) {
    if ( target === 'popup' ) {
        joms.popup.stream.showLikes( id, target );
        return;
    }

    joms.ajax({
        func: 'system,ajaxStreamShowLikes',
        data: [ id ],
        callback: function( json ) {
            var streams;
            if ( json.success ) {
                streams = container.find('.joms-stream[data-stream-id=' + id + ']');
                streams.find('.joms-stream__status').html( json.html || '' );
            }
        }
    });
}

function showComments( id, type ) {
    joms.popup.stream.showComments( id, type );
}

function showOthers( id ) {
    joms.popup.stream.showOthers( id );
}

function report( id ) {
    joms.popup.stream.report( id );
}

function addFeatured( id ) {
    joms.popup.stream.addFeatured( id );
}

function removeFeatured( id ) {
    joms.popup.stream.removeFeatured( id );
}

function toggleText( id ) {
    var $text = $( '.joms-js--stream-text-' + id ),
        $full = $( '.joms-js--stream-textfull-' + id ),
        $btn  = $( '.joms-js--stream-texttoggle-' + id );

    if ( $full.is(':visible') ) {
        $full.hide();
        $text.show();
        $btn.html( $btn.data('lang-more') );
    } else {
        $text.hide();
        $full.show();
        $btn.html( $btn.data('lang-less') );
    }
}

// Exports.
return {
    start: initialize,
    stop: uninitialize,
    like: like,
    unlike: unlike,
    edit: edit,
    save: save,
    cancel: cancel,
    editLocation: editLocation,
    remove: remove,
    removeLocation: removeLocation,
    removeMood: removeMood,
    removeTag: removeTag,
    selectPrivacy: selectPrivacy,
    share: share,
    hide: hide,
    ignoreUser: ignoreUser,
    showLikes: showLikes,
    showComments: showComments,
    showOthers: showOthers,
    report: report,
    toggleText: toggleText,
    addFeatured: addFeatured,
    removeFeatured: removeFeatured
};

});

(function( root, $, factory ) {

    joms.util || (joms.util = {});
    joms.util.photos = factory( root, $ );

})( window, joms.jQuery, function( window, $ ) {

var containers = [],
    maxHeightThreshold = 180;

$( window ).resize( joms._.debounce(function() {
    _rearrange();
}, 500 ));

function arrange( $ct ) {
    var $children = $ct.children(),
        width = $ct.width();

    $children.css({
        display: 'block',
        'float': 'left',
        margin: '1px 0',
        overflow: 'hidden',
        padding: 0,
        position: 'relative'
    }).each(function() {
        var $el = $( this );
        $el.data({
            width: $el.width(),
            height: $el.height()
        });
    });

    _arrange( width, $children );

    // Add to registered container.
    containers.push( $ct );
}

function _arrange( ctWidth, $children ) {
    var from = 0,
        len = $children.length;

    $children.each(function( index ) {
        var divider, data, height, i;

        divider = 0;
        for ( i = from; i <= index; i++ ) {
            data = $children.eq(i).data();
            divider += data.width / data.height;
        }

        height = ctWidth / divider;

        if ( height <= maxHeightThreshold ) {
            for ( i = from; i <= index; i++ ) {
                $children.eq( i ).find('img').css({ height: height });
            }
            from = index + 1;
        } else if ( i === len ) {
            for ( i = from; i <= len; i++ ) {
                $children.eq( i ).nextAll().andSelf()
                    .css({ height: maxHeightThreshold })
                    .find('img').css({ height: height });
            }
        }
    });

    // fix ff issue
    $children.css('border', '1px solid transparent');
    setTimeout(function() {
        $children.css('border', '');
    });
}

function _rearrange() {
    var i, $ct;

    for ( i = 0; i < containers.length; i++ ) {
        $ct = containers[i];
        _arrange( $ct.width(), $ct.children() );
    }
}

// Exports.
return {
    arrange: arrange
};

});

define("utils/photos", function(){});

(function( root, $, factory ) {

    joms.view || (joms.view = {});
    joms.view.streams = factory( root, $ );

    define('views/streams',[ 'utils/hovercard', 'utils/photos', 'utils/video', 'functions/tagging' ], function() {
        return joms.view.streams;
    });

})( window, joms.jQuery, function( window, $ ) {

var container, adAgencySettings, adAgencyImpressions;

function initialize() {
    uninitialize();
    container = $('.joms-stream__wrapper');

    // Initialize only when container is available.
    if ( !container.length )
        return;

    // Initialize comment box.
    initInputbox();

    // Initialize jquery montage plugin.
    initPhotoArranger();

    // Initialize media element.
    initVideoPlayers();

    // Disable adagency, infinite scroll, and recent activities on single activity page.
    if ( !window.joms_singleactivity && !window.joms_filter_keyword ) {

        // Initialize ad agency.
        if ( +window.joms_adagency ) {
            initAdAgency();
        }

        // Initialize infinite scroll.
        if ( +window.joms_infinitescroll ) {
            setupAutoLoadActivities();
        }

        // Get recent activities.
        if ( +window.joms_enable_refresh ) {
            getRecentActivitiesCount();
        }
    }

    var filterbar = document.getElementsByClassName('joms-activity-filter-action');
    if ( filterbar && filterbar.length ) {
        window.FastClick.attach( filterbar[0] );
    }
}

function uninitialize() {
    if ( container ) {
        container.off();
    }
}

function initPhotoArranger() {
    var initialized = '.joms-js--initialized',
        $containers = $('.joms-media--images').not( initialized );

    $containers.each(function() {
        var $ct = $( this ),
            $imgs = $ct.find('img'),
            counter = 0;

        $imgs.each(function() {
            var $img = $( this );

            $('<img>').load(function() {
                counter++;
                if ( counter === $imgs.length ) {
                    $ct.siblings('.joms-media--loading').remove();
                    $ct.addClass( initialized.substr(1) );
                    $imgs.show();
                    joms.util.photos.arrange( $ct );
                }

            }).attr( 'src', $img.attr('src') );
        });
    });
}

function initVideoPlayers() {
    var initialized = '.joms-js--initialized',
        cssVideos = '.joms-js--video',
        videos = $('.joms-stream__body').find( cssVideos ).not( initialized ).addClass( initialized.substr(1) );

    if ( !videos.length ) {
        return;
    }

    joms.loadCSS( joms.ASSETS_URL + 'vendors/mediaelement/mediaelementplayer.min.css' );
    videos.on( 'click.joms-video', cssVideos + '-play', function() {
        var $el = $( this ).closest( cssVideos );
        joms.util.video.play( $el, $el.data() );
    });

    // if ( joms.ios ) {
    //     setTimeout(function() {
    //         videos.find( cssVideos + '-play' ).click();
    //     }, 1000 );
    // }
}

function initInputbox() {
    joms.fn.tagging.initInputbox();
}

function getEdgeStreamId( edge ) {
    var stream, ids;

    stream = container.find('.joms-stream').not('.joms-stream--adagency');
    if ( !stream.length )
        return 0;

    ids = [];
    stream.each(function() {
        ids.push( +$(this).data('stream-id') );
    });

    return ids[ edge === 'last' ? ids.length - 1 : 0 ];
}

function getFilter() {
    var $ct = container.children('.joms-stream__container');
    return {
        filter: $ct.data('filter'),
        filterId: $ct.data('filterid'),
        filterValue: $ct.data('filter-value')
    };
}

function getRecentActivitiesCount() {
    var o = getRecentActivitiesCount,
        id, filter;

    if ( o.loading )
        return;

    if ( !( id = window.joms_newest_stream_id ) ) {
        console.warn('Variable `window.joms_newest_stream_id` not found.');
        return;
    }

    filter = getFilter();

    o.loading = true;
    o.xhr && o.xhr.abort();
    o.xhr = joms.ajax({
        func: 'activities,ajaxGetRecentActivitiesCount',
        data: [ id, filter.filter, filter.filterId, filter.filterValue ],
        callback: function( json ) {
            var count   = +json.count,
                delay   = +json.nextPingDelay,
                $latest = $('.joms-js--stream-latest'),
                $link;

            o.loading = false;
            o.xhr     = null;

            if ( !window.joms_postbox_posting ) {
                if ( count > 0 ) {
                    $link = $( '<a href="javascript:">' + json.html + '</a>' );
                    $link.on( 'click', getRecentActivities );
                    $latest.html( $link ).show();
                } else {
                    $latest.hide().empty();
                }
            }

            if ( delay > 0 ) {
                joms._.delay( getRecentActivitiesCount, delay );
            }
        }
    });
}

function getRecentActivities() {
    var o = getRecentActivities,
        id, filter;

    if ( o.loading )
        return;

    if ( !( id = getEdgeStreamId() ) )
        return;

    filter = getFilter();

    o.loading = true;
    o.xhr && o.xhr.abort();
    o.xhr = joms.ajax({
        func: 'activities,ajaxGetRecentActivities',
        data: [ id, filter.filter, filter.filterId, filter.filterValue ],
        callback: function( json ) {
            var $items = $( $.trim( json.html ) ).filter('.joms-stream__wrapper').find('.joms-stream'),
                $latest = $('.joms-js--stream-latest'),
                i;

            // update newest stream id
            if ( json.newest_stream_id ) {
                window.joms_newest_stream_id = +json.newest_stream_id;
            }

            o.loading = false;

            if ( $items.length ) {
                for ( i = $items.length - 1; i >= 0; i-- ) {
                    // Prevent duplicated stream.
                    if ( ! ( $('.joms-js--stream-' + $items.eq(i).data('stream-id') ).length ) ) {
                        container.find('.joms-stream__container').prepend( $items.eq( i ) );
                    }
                }
            }

            $latest.hide();

            initInputbox();
            initPhotoArranger();
            initVideoPlayers();
        }
    });
}

function getOlderActivities() {
    var o = getOlderActivities,
        id, filter, btn, loading;

    if ( o.loading )
        return;

    if ( !( id = getEdgeStreamId('last') ) )
        return;

    filter = getFilter();

    o.loading = true;
    btn = container.find('#activity-more');
    loading = btn.find('.loading');
    btn = btn.find('.joms-button--primary');

    btn.hide();
    loading.show();

    joms.ajax({
        func: 'activities,ajaxGetOlderActivities',
        data: [ id, filter.filter, filter.filterId, filter.filterValue ],
        callback: function( json ) {
            var isLast = false,
                $items;

            o.loading = false;
            loading.hide();

            if ( json.html ) {
                $items = $( $.trim( json.html ) ).filter('.joms-stream__wrapper').find('.joms-stream');
                if ( $items.length ) {
                    container.find('.joms-stream__container').append( $items );
                } else {
                    isLast = true;
                }
            }

            initInputbox();
            initPhotoArranger();
            initVideoPlayers();
            injectAdAgencyItem();

            if ( !isLast ) {
                btn.show();
            }
        }
    });
}

var setupAutoLoadActivities = function() {
    var load, win, doc, treshhold, lastScrollTop;

    if ( joms.mobile )
        return false;

    $('.joms-stream__loadmore').find('a').hide();
    win = $( window );
    doc = $( document );

    treshhold = Math.max( +window.joms_autoloadtrigger || 0, 20 );
    lastScrollTop = 0;

    load = function() {
        var scrollTop = win.scrollTop(),
            winHeight = win.height(),
            direction, id, filter;

        direction = scrollTop < lastScrollTop ? 'up' : 'down';
        lastScrollTop = scrollTop;

        if ( direction !== 'down' ) {
            return;
        }

        if ( ( scrollTop + winHeight ) < ( doc.height() - treshhold ) ) {
            return;
        }

        if ( load.loading ) {
            return;
        }

        load.loading = true;
        container.find('.joms-stream__loadmore .loading').show();

        if ( !( id = getEdgeStreamId('last') ) ) {
            return;
        }

        filter = getFilter();

        joms.ajax({
            func: 'activities,ajaxGetOlderActivities',
            data: [ id, filter.filter, filter.filterId, filter.filterValue ],
            callback: function( json ) {
                var isLast = false,
                    $items;

                container.find('.joms-stream__loadmore .loading').hide();

                if ( json.html ) {
                    $items = $( $.trim( json.html ) ).filter('.joms-stream__wrapper').find('.joms-stream');
                    if ( $items.length ) {
                        container.find('.joms-stream__container').append( $items );
                    } else {
                        isLast = true;
                    }
                }

                initInputbox();
                initPhotoArranger();
                initVideoPlayers();
                injectAdAgencyItem();

                if ( isLast )
                    return;

                load.loading = false;
            }
        });

    }

    win.on( 'scroll', load );
};

function initAdAgency() {
    joms.ajax({
        func: 'system,ajaxGetAdagency',
        callback: function( json ) {
            adAgencySettings = json || {};

            // Shuffle ads.
            if ( adAgencySettings.ads && adAgencySettings.ads.length ) {
                adAgencySettings.ads = joms._.shuffle( adAgencySettings.ads );
            }

            injectAdAgencyItem();
        }
    });
}

function createAdAgencyItem( config, ad ) {
    var html;

    html  = '<div data-stream-type="adagency" class="joms-stream joms-stream--adagency">';
    html +=   '<div class="joms-stream__header">';
    html +=     '<div class="joms-avatar--stream">';
    html +=       '<a href="' + ad.on_click_url + '" target="_blank" onclick="window.open(\'' + ad.on_click_url + '\'); return false;">';
    html +=         '<img src="' + ad.banner_avatar + '">';
    html +=       '</a>';
    html +=     '</div>';
    html +=     '<div class="joms-stream__meta">';
    html +=       '<a class="joms-stream__user" href="' + ad.on_click_url + '" target="_blank" onclick="window.open(\'' + ad.on_click_url + '\'); return false;">' + ad.banner_headline + '</a>';
    html +=       '<a href="' + ad.on_click_url + '" target="_blank" onclick="window.open(\'' + ad.on_click_url + '\'); return false;"><span class="joms-stream__time"><small>' + (ad.short_url_to_promote || ad.url_to_promote) + '</small></span></a>';
    html +=     '</div>';
    html +=   '</div>';
    html +=   '<div class="joms-stream__body">';
    html +=     '<p>' + ad.banner_text + '</p>';
    html +=     '<div class="joms-media--image">';
    html +=       '<a href="' + ad.on_click_url + '" target="_blank" onclick="window.open(\'' + ad.on_click_url + '\'); return false;">';
    html +=         '<img src="' + ad.banner_image_content + '">';
    html +=       '</a>';
    html +=     '</div>';
    html +=   '</div>';

    if ( +config.show_sponsored_stream_info || +config.show_create_ad_link ) {
        html += '<div class="joms-stream__actions">';
        if ( +config.show_sponsored_stream_info ) {
            html += '<span style="float:left">' + config.sponsored_stream_info_text + '</span>';
        }
        if ( +config.show_create_ad_link ) {
            html += '<a href="' + config.create_ad_link + '" style="float:right">' + config.create_ad_link_text + '</a>';
        }
        html += '<div style="clear:both"></div>';
        html += '</div>';
    }

    html += '</div>';
    return html;
}

function injectAdAgencyItem() {
    var ads, config, after, every, counter, isAfter, pageMap, isLoggedIn, isPublic;

    if ( !(adAgencySettings && adAgencySettings.config && adAgencySettings.ads && adAgencySettings.ads.length) ) {
        return;
    }

    ads     = adAgencySettings.ads;
    config  = adAgencySettings.config;
    after   = +config.display_stream_ads_after_value;
    every   = +config.display_stream_ads_every_value;
    isAfter = +config.display_stream_ads;
    counter = 0;

    isLoggedIn = +window.joms_my_id;
    if ( !isLoggedIn ) {
        for ( var i = ads.length - 1; i >= 0; i-- ) {
            isPublic = +ads[i].banner_access;
            if ( !isPublic ) {
                ads.splice( i, 1 );
            }
        }
    }

    if ( !ads.length ) {
        return;
    }

    pageMap = {
        frontpage : 'front_page_stream',
        profile   : 'profile_stream',
        groups    : 'group_stream',
        events    : 'event_stream'
    };

    if ( ( config.js_stream_ads_on || [] ).indexOf( pageMap[ window.joms_page ] ) < 0 ) {
        return;
    }

    container.find('.joms-stream').not('.joms-stream--adagency').each(function( i ) {
        var elem, next;

        // Show ad after 'x' stream items.
        if ( isAfter ) {

            if ( !after ) {
                return false;
            }

            if ( i === after - 1 ) {
                elem = $( this );
                next = elem.next();
                if ( !next.length || !next.hasClass('joms-stream--adagency') ) {
                    elem.after( createAdAgencyItem( config, ads[ counter ] ) );
                    increaseAdAgencyImpression( ads[ counter ] );
                }

                return false;
            }

        // Show ad every 'x' stream items.
        } else {

            if ( !every ) {
                return false;
            }

            if ( (i + 1) % every === 0 ) {
                elem = $( this );
                next = elem.next();
                if ( !next.length || !next.hasClass('joms-stream--adagency') ) {
                    elem.after( createAdAgencyItem( config, ads[ counter % ads.length ] ) );
                    increaseAdAgencyImpression( ads[ counter % ads.length ] );
                }

                counter++;
            }

        }
    });
}

function increaseAdAgencyImpression( ad ) {
    var id = [
        ad.advertiser_id,
        ad.campaign_id,
        ad.banner_id,
        ad.campaign_type
    ].join('-');

    adAgencyImpressions || (adAgencyImpressions = {});

    if ( adAgencyImpressions[ id ] ) {
        return;
    }

    adAgencyImpressions[ id ] = true;

    joms.ajax({
        func: 'system,ajaxAdagencyGetImpression',
        data: [ ad.advertiser_id, ad.campaign_id, ad.banner_id, ad.campaign_type ],
        callback: function() {}
    });
}

// Exports.
return {
    start: initialize,
    stop: uninitialize,
    loadMore: getOlderActivities
};

});

(function( root, $, factory ) {

    joms.view || (joms.view = {});
    joms.view.toolbar = factory( root, $ );

})( window, joms.jQuery, function( window, $ ) {

var wrapper, buttonMain, buttonUser, buttonSubMenu, xhr, lastbtn;

function hideMenu( e ) {
    var nohide = $( e.target ).closest('.joms-trigger__menu--main, .joms-trigger__menu--user, .joms-menu, .joms-menu--user');
    if ( nohide.length ) return;
    if ( wrapper.hasClass('show-menu') || wrapper.hasClass('show-menu--user') ) {
        e.preventDefault();
        e.stopPropagation();
        wrapper.removeClass('show-menu');
        wrapper.removeClass('show-menu--user');
    }
}

function toggleMenu( e ) {
    e.stopPropagation();
    wrapper.toggleClass('show-menu');
}

function toggleUserMenu( e ) {
    e.stopPropagation();
    wrapper.toggleClass('show-menu--user');
}

function toggleSubMenu( e ) {
    var el = $( e.currentTarget ).closest('li');
    if ( el.hasClass('show-submenu') ) {
        el.removeClass('show-submenu');
    } else {
        el.addClass('show-submenu').siblings().removeClass('show-submenu');
    }
}

function start() {
    if ( !wrapper ) wrapper = $('.jomsocial-wrapper');
    if ( !buttonMain ) buttonMain = $('.joms-trigger__menu--main');
    if ( !buttonUser ) buttonUser = $('.joms-trigger__menu--user');
    if ( !buttonSubMenu ) buttonSubMenu = $('.joms-menu__toggle');

    stop();

    wrapper.on( 'click.menu', hideMenu );

    wrapper.on( 'click.menu', '.joms-js--has-dropdown', function( e ) {
        e.preventDefault();
        e.stopPropagation();
        window.location = $( e.currentTarget ).attr('href');
    });

    wrapper.on( 'mouseenter.menu', '.joms-toolbar--desktop > ul > li > a.joms-js--has-dropdown', function( e ) {
        var btn = $( e.currentTarget );
        if ( ! btn.siblings('ul.joms-dropdown').is(':visible') ) {
            lastbtn = btn.trigger('click.dropdown');
        }
    });

    wrapper.on( 'mouseleave.menu', '.joms-toolbar--desktop', function() {
        if ( lastbtn ) {
            lastbtn.trigger('collapse.dropdown');
            lastbtn = undefined;
        }
    });

    buttonMain.on( 'click.menu', toggleMenu );
    buttonUser.on( 'click.menu', toggleUserMenu );
    buttonSubMenu.on( 'click.submenu', toggleSubMenu );

    getNotifications();
}

function stop() {
    if ( wrapper ) {
        wrapper.off( 'click.menu' );
        wrapper.off( 'click.menu', '.joms-js--has-dropdown' );
        wrapper.off( 'mouseenter.menu', '.joms-toolbar--desktop > ul > li > a.joms-js--has-dropdown' );
        wrapper.off( 'mouseleave.menu', '.joms-toolbar--desktop' );
    }

    if ( buttonMain ) buttonMain.off('click.menu');
    if ( buttonUser ) buttonUser.off('click.menu');
    if ( buttonSubMenu ) buttonSubMenu.off('click.submenu');
}

function notificationGeneral() {
    joms.ajax({
        func: 'notification,ajaxGetNotification',
        data: [ '' ],
        callback: function( json ) {
            var elem;
            if ( json.html ) {
                elem = $('.joms-popover--toolbar-general');
                elem.html( json.html );
            }
        }
    });
}

function notificationFriend() {
    joms.ajax({
        func: 'notification,ajaxGetRequest',
        data: [ '' ],
        callback: function( json ) {
            var elem;
            if ( json.html ) {
                elem = $('.joms-popover--toolbar-friendrequest');
                elem.html( json.html );
                elem.off( 'click', '.joms-button__approve' ).on( 'click', '.joms-button__approve', notificationFriendReject );
                elem.off( 'click', '.joms-button__reject' ).on( 'click', '.joms-button__reject', notificationFriendApprove );
            }
        }
    });
}

function notificationFriendReject( e ) {
    var elem = $( e.currentTarget ),
        id = elem.data('connection');

    joms.ajax({
        func: 'friends,ajaxRejectRequest',
        data: [ id ],
        callback: function( json ) {
            elem = $('.joms-js__friend-request-' + id);
            elem.find('.joms-popover__actions').remove();
            elem.find('.joms-popover__content').html( json.error || json.message );
            notificationCounter( 'friendrequest', -1 );
        }
    });
}

function notificationFriendApprove( e ) {
    var elem = $( e.currentTarget ),
        id = elem.data('connection');

    joms.ajax({
        func: 'friends,ajaxApproveRequest',
        data: [ id ],
        callback: function( json ) {
            elem = $('.joms-js__friend-request-' + id);
            elem.find('.joms-popover__actions').remove();
            elem.find('.joms-popover__content').html( json.error || json.message );
            notificationCounter( 'friendrequest', -1 );
        }
    });
}

function notificationPm() {
    joms.ajax({
        func: 'notification,ajaxGetInbox',
        data: [ '' ],
        callback: function( json ) {
            var elem;
            if ( json.html ) {
                elem = $('.joms-popover--toolbar-pm');
                elem.html( json.html );
            }
        }
    });
}

function notificationCounter( type, count ) {
    var counters;

    if ([ 'general', 'friendrequest', 'pm' ].indexOf( type ) < 0)
        return;

    counters = $( '.joms-notifications__label--' + type );
    count = +counters.eq(0).text() + count;
    counters.html( count > 0 ? count : '' );
}

function search( elem ) {
    var keyword = elem,
        rTrim = /^\s+|\s+$/g,
        field, loading, viewall;

    if ( typeof elem !== 'string' ) {
        keyword = $( elem ).val();
    }

    keyword = keyword || '';
    if ( !keyword.replace(rTrim, '') )
        return;

    if ( xhr ) {
        xhr.abort();
    }

    elem = $('.joms-popover--toolbar-search');
    field = elem.find('.joms-js--field');
    loading = elem.find('.joms-js--loading');
    viewall = elem.find('.joms-js--viewall');

    elem.find('li:not(.joms-js--noremove)').remove();
    viewall.hide();
    loading.show();

    xhr = joms.ajax({
        func: 'search,ajaxSearch',
        data: [ keyword ],
        callback: function( json ) {
            var html, i, form, max, btn;

            loading.hide();

            if ( json.error ) {
                html = '<li class="joms-js--error">' + json.error + '</li>';
                field.after( html );
                return;
            }

            if ( json.length ) {
                html = '';
                max = Math.min( 3, json.length );
                for ( i = 0; i < max; i++ ) {
                    html += '<li><div class="joms-popover__avatar"><div class="joms-avatar">';
                    html += '<img src="' + json[i].thumb + '"></div></div>';
                    html += '<div class="joms-popover__content">';
                    html += '<h5><a href="' + json[i].url + '">' + json[i].name + '</a></h5>';
                    html += '</div></li>';
                }

                form = viewall.find('form');
                form.find('input').val( keyword );
                viewall.off( 'click', 'a' ).on( 'click', 'a', function() {
                    form[0].submit();
                });

                btn = viewall.find('a');
                btn.html( btn.data('lang').replace( '%1$s', json.length ) );

                field.after( html );
                viewall.show();
                elem.show();
            }
        }
    });
}

function getNotifications() {
    var viewerId = +window.joms_my_id;
    if ( !viewerId )
        return;

    // do not call it if elements not found
    if ( ! $('.joms-js--notiflabel-general').length )
        return;

    joms.ajax({
        func: 'activities,ajaxGetTotalNotifications',
        callback: function( json ) {
            var generals, friendrequests, pms, delay, title;

            json || (json = {});

            generals       = json.newNotificationCount;
            friendrequests = json.newFriendInviteCount;
            pms            = json.newMessageCount;
            delay          = +json.nextPingDelay;

            if ( typeof generals !== 'undefined' ) {
                generals = +generals || '';
                $('.joms-js--notiflabel-general').html( generals );

                // Also update document's title.
                title = document.title;
                title = title.replace( /^\(\d+\)\s/, '' );
                title = ( generals ? '(' + generals + ') ' : '' ) + title;
                document.title = title;
            }

            if ( typeof friendrequests !== 'undefined' ) {
                $('.joms-js--notiflabel-frequest').html( +friendrequests || '' );
            }

            if ( typeof pms !== 'undefined' ) {
                $('.joms-js--notiflabel-inbox').html( +pms || '' );
            }

            if ( delay > 0 ) {
                joms._.delay( getNotifications, delay );
            }
        }
    });
}

// Exports.
return {
    start: start,
    stop: stop,
    notificationGeneral: notificationGeneral,
    notificationFriend: notificationFriend,
    notificationPm: notificationPm,
    notificationCounter: notificationCounter,
    search: search
};

});

define("views/toolbar", function(){});

(function( root, $, factory ) {

    joms.fn || (joms.fn = {});
    joms.fn.announcement = factory( root, $ );

    define('functions/announcement',[],function() {
        return joms.fn.announcement;
    });

})( window, joms.jQuery, function( window, $ ) {

function edit( groupid, id ) {
    $( '.joms-js--announcement-view-' + groupid + '-' + id ).hide();
    $( '.joms-js--announcement-edit-' + groupid + '-' + id ).show();
    $( '.joms-subnav,.joms-subnav--desktop' ).hide();
    $( '.joms-sidebar' ).hide();
    $( '.joms-main' ).css({ padding: 0, width: '100%' });
}

function editCancel( groupid, id ) {
    $( '.joms-js--announcement-edit-' + groupid + '-' + id ).hide();
    $( '.joms-js--announcement-view-' + groupid + '-' + id ).show();
    $( '.joms-subnav,.joms-subnav--desktop' ).css( 'display', '' );
    $( '.joms-main' ).css({ padding: '', width: '' });
    $( '.joms-sidebar' ).show();
}

// Exports.
return {
    edit: edit,
    editCancel: editCancel
};

});

(function( root, $, factory ) {

    joms.fn || (joms.fn = {});
    joms.fn.facebook = factory( root, $ );

    define('functions/facebook',[],function() {
        return joms.fn.facebook;
    });

})( window, joms.jQuery, function() {

function update() {
    joms.ajax({
        func: 'connect,ajaxUpdate',
        data: [ '' ],
        callback: function( json ) {
            console.log( json );
        }
    });
}

// Exports.
return {
    update: update
};

});

(function( root, $, factory ) {

    joms.fn || (joms.fn = {});
    joms.fn.notification = factory( root, $ );

    define('functions/notification',[],function() {
        return joms.fn.notification;
    });

})( window, joms.jQuery, function( window, $ ) {

var requests = [];

function updateCounter( type, id, count ) {
    var $el;

    id = type + '-' + id;

    // Prevent double/multiple request for one notification.
    if ( requests.indexOf( id ) >= 0 ) {
        return;
    }

    $el = $( '.joms-js--notiflabel-' + type );

    if ( requests.indexOf( id ) < 0 ) {
        requests.push( id );
        count = +$el.eq(0).text() + count;
        $el.html( count > 0 ? count : '' );
    }
}

// Exports.
return {
    updateCounter: updateCounter
};

});

(function( root, $, factory ) {

    joms.fn || (joms.fn = {});
    joms.fn.invitation = factory( root, $ );

    define('functions/invitation',[ 'functions/notification' ], function() {
        return joms.fn.invitation;
    });

})( window, joms.jQuery, function( window, $ ) {

function accept( type, id ) {
    var func = type === 'group' ? 'notification,ajaxGroupJoinInvitation' : 'events,ajaxJoinInvitation',
        data = [ id ];

    joms.ajax({
        func: func,
        data: data,
        callback: function( json ) {
            _update( type, id, json );
        }
    });
}

function reject( type, id ) {
    var func = type === 'group' ? 'notification,ajaxGroupRejectInvitation' : 'events,ajaxRejectInvitation',
        data = [ id ];

    joms.ajax({
        func: func,
        data: data,
        callback: function( json ) {
            _update( type, id, json );
        }
    });
}

function _update( type, id, json ) {
    $( '.joms-js--invitation-buttons-' + type + '-' + id ).remove();
    $( '.joms-js--invitation-notice-' + type + '-' + id ).html( json && json.message || '' );
    joms.fn.notification.updateCounter( 'general', id, -1 );
}

// Exports.
return {
    accept: accept,
    reject: reject
};

});

(function( root, $, factory ) {

    joms.util || (joms.util = {});
    joms.util.field = factory( root, $ );

})( window, joms.jQuery, function( window, $ ) {

function _createFileWrapper() {
    return [
        '<div data-wrap="file" style="width:350px;max-width:100%;position:relative;overflow:hidden">',
            '<input type="text" class="joms-input" readonly="readonly" placeholder="', (window.joms_lang && window.joms_lang.COM_COMMUNITY_SELECT_FILE || 'Select file'), '.."',
                'style="margin-bottom:2px">',
        '</div>'
    ].join('');
}

function _extractFileName( path ) {
    var matches = path.match( /[^\\\/]+$/ );
    if ( matches && matches[0] ) {
        return matches[0];
    }

    return '';
}

// Exports.
return {
    file: function( $elems ) {
        $elems = $( $elems );
        $elems.each(function( i, $elem ) {
            var $wrapper;

            $elem = $( $elem );
            $wrapper = $elem.parent();

            if ( ! $wrapper.data('wrap') ) {
                $wrapper = $( _createFileWrapper() );
                $elem.before( $wrapper );
                $elem.hide();
                $elem.appendTo( $wrapper );
                $elem.css({
                    cursor: 'pointer',
                    position: 'absolute',
                    right: 0,
                    top: 0,
                    width: '100%',
                    height: '100%',
                    opacity: 0
                });
                $elem.show();
            }

            // On file selection.
            $elem.off( 'change.joms-file' );
            $elem.on( 'change.joms-file', function() {
                $wrapper.find('.joms-input').val( _extractFileName( $(this).val() ) );
            });

        });
    }
};

});

define("utils/field", function(){});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.info = factory( root, $ );

    define('popups/info',[ 'utils/popup' ], function() {
        return joms.popup.info;
    });

})( window, joms.jQuery, function() {

function render( popup, title, content ) {
    popup.items[0] = {
        type: 'inline',
        src: buildHtml( title, content )
    };

    popup.updateItemHTML();
}

function buildHtml( title, content ) {
    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', ( title || '&nbsp;' ), '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( content || '' ), '</div>',
        '<div class="joms-popup__action">',
        '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( title, content ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, title, content );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.login = factory( root, $ );

    define('popups/login',[ 'utils/popup' ], function() {
        return joms.popup.login;
    });

})( window, joms.jQuery, function() {

var popup, elem;

function login( _popup, _json ) {
    if ( _json && typeof _json === 'object' ) {
        render( _popup, _json );
        return;
    }

    var uri = _json;
    if ( typeof _json !== 'string' ) {
        uri = window.location.href;
        uri = uri.replace(/#.*$/, '');
    }

    joms.ajax({
        func: 'system,ajaxLogin',
        data: [ uri ],
        callback: function( json ) {
            render( _popup, json );
        }
    });
}

function render( _popup, _json ) {
    if ( elem ) elem.off();

    popup = _popup;
    popup.items[0] = {
        type: 'inline',
        src: buildHtml( _json )
    };

    popup.updateItemHTML();

    elem = popup.contentContainer;
    elem.find('form').on( 'submit', send );
}

function send( e ) {
    e.preventDefault();
    e.stopPropagation();
    joms.ajax({
        func: 'system,ajaxGetLoginFormToken',
        data: [],
        callback: function( json ) {
            var form = elem.find('form');
            if ( json.token ) {
                form.find('.joms-js--token input').prop('name', json.token);
            }
            form.off('submit');
            form.find('[name=submit]').click();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock joms-popup--500">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.html || '' ), '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( json ) {
    joms.util.popup.prepare(function( mfp ) {
        login( mfp, json );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.album || (joms.popup.album = {});
    joms.popup.album.addFeatured = factory( root, $ );

    define('popups/album.addfeatured',[ 'utils/popup' ], function() {
        return joms.popup.album.addFeatured;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'photos,ajaxAddFeatured',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.st.callbacks || (popup.st.callbacks = {});
            popup.st.callbacks.close = function() {
                window.location.reload();
            };

            popup.updateItemHTML();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.html || json.error || '' ), '</div>',
        '<div class="joms-popup__action">',
        '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.album || (joms.popup.album = {});
    joms.popup.album.removeFeatured = factory( root, $ );

    define('popups/album.removefeatured',[ 'utils/popup' ], function() {
        return joms.popup.album.removeFeatured;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'photos,ajaxRemoveFeatured',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.st.callbacks || (popup.st.callbacks = {});
            popup.st.callbacks.close = function() {
                window.location.reload();
            };

            popup.updateItemHTML();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.html || json.error || '' ), '</div>',
        '<div class="joms-popup__action">',
        '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.album || (joms.popup.album = {});
    joms.popup.album.remove = factory( root );

    define('popups/album.remove',[ 'utils/popup' ], function() {
        return joms.popup.album.remove;
    });

})( window, function() {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'photos,ajaxRemoveAlbum',
        data: [ id, 'myphotos' ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '[data-ui-object=popup-button-cancel]', cancel );
            elem.on( 'click', '[data-ui-object=popup-button-save]', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    elem.find('form').submit();
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div data-ui-object="popup-step-1"', ( json.error ? ' class="joms-popup__hide"' : '' ), '>',
            '<div class="joms-popup__content">', json.message, '</div>',
            '<div class="joms-popup__action">',
            '<a href="javascript:" class="joms-button--neutral joms-button--small joms-left" data-ui-object="popup-button-cancel">', json.btnCancel, '</a> &nbsp;',
            '<button class="joms-button--primary joms-button--small" data-ui-object="popup-button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '<div data-ui-object="popup-step-2"', ( json.error ? '' : ' class="joms-popup__hide"' ), '>',
            '<div class="joms-popup__content joms-popup__content--single" data-ui-object="popup-message">', (json.error || ''), '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.album = factory( root, joms.popup.album || {});

    define('popups/album',[
        'popups/album.addfeatured',
        'popups/album.removefeatured',
        'popups/album.remove'
    ], function() {
        return joms.popup.album;
    });

})( window, function( window, sub ) {

// Exports.
return joms._.extend({}, sub );

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.announcement || (joms.popup.announcement = {});
    joms.popup.announcement.remove = factory( root );

    define('popups/announcement.remove',[ 'utils/popup' ], function() {
        return joms.popup.announcement.remove;
    });

})( window, function() {

var popup, elem, groupid, id;

function render( _popup, _groupid, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    groupid = _groupid;
    id = _id;

    joms.ajax({
        func: 'groups,ajaxShowRemoveBulletin',
        data: [ groupid, id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    elem.find('form')[0].submit();
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1">',
            '<div class="joms-popup__content">', json.html, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnNo, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-popup__hide joms-js--step2">',
            '<div class="joms-popup__content joms-popup__content--single"></div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( groupid, id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, groupid, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.announcement = factory( root, joms.popup.announcement || {});

    define('popups/announcement',[
        'popups/announcement.remove'
    ], function() {
        return joms.popup.announcement;
    });

})( window, function( window, sub ) {

// Exports.
return joms._.extend({}, sub );

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.app || (joms.popup.app = {});
    joms.popup.app.about = factory( root, $ );

    define('popups/app.about',[ 'utils/popup' ], function() {
        return joms.popup.app.about;
    });

})( window, joms.jQuery, function() {

var popup, elem, name;

function render( _popup, _name ) {
    if ( elem ) elem.off();
    popup = _popup;
    name = _name;

    joms.ajax({
        func: 'apps,ajaxShowAbout',
        data: [ name ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.html || '' ), '</div>',
        '<div class="joms-popup__action">',
        '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( name ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, name );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.app || (joms.popup.app = {});
    joms.popup.app.browse = factory( root, $ );

    define('popups/app.browse',[ 'utils/popup' ], function() {
        return joms.popup.app.browse;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, pos;

function render( _popup, _pos ) {
    if ( elem ) elem.off();
    popup = _popup;
    pos = _pos;

    joms.ajax({
        func: 'apps,ajaxBrowse',
        data: [ pos ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', 'a[data-appname]', save );
            elem.on( 'click', '.joms-js--btn-view-all', viewAll );

        }
    });
}

function save( e ) {
    var el = $( e.target ),
        appName = el.data('appname'),
        position = el.data('position'),
        stacked = position.indexOf('-stacked') >= 0,
        tabTemplate = '<a href="#joms-js--app-app_id" class="no-padding joms-js--app-tab-app_id active"><div class="joms-tab__bar--button"><span class="title">app_title</span></div></a>';

    joms.ajax({
        func: 'apps,ajaxAddApp',
        data: [ appName, position ],
        callback: function( json ) {
            var $pos, $btn, $tab;
            if ( json.success ) {
                $pos = $( '.joms-js--app-pos-' + pos );
                $tab = $pos.find('.joms-tab__bar').eq(0);
                if (stacked) {
                    $tab.before( $(json.item).show() );
                } else {
                    $btn = $tab.find('.joms-js--app-new');
                    $btn.prevAll().removeClass('active');
                    $btn.before( tabTemplate.replace( /app_id/g, json.id ).replace( /app_title/, json.title ) );
                    $pos.find('.joms-tab__content').hide();
                    $pos.append( $(json.item).show() );
                }
                getSetting( json.id, appName );
            }
        }
    });
}

function getSetting( appId, appName ) {
    joms.ajax({
        func: 'apps,ajaxShowSettings',
        data: [ appId, appName ],
        callback: function( json ) {
            elem.off( 'click', 'a[data-appname]' );
            elem.html( buildHtml( json, 'setting' ) );
            elem.on( 'click', '.joms-popup__content,.joms-popup__action', function( e ) {
                e.stopPropagation();
                return false;
            });
            elem.on( 'click', '[data-ui-object=popup-button-save]', function() {
                saveSetting();
            });
        }
    });
}

function saveSetting() {
    var $form = elem.find('form'),
        params = $form.serializeArray(),
        data = [],
        i;

    for ( i = 0; i < params.length; i++ ) {
        data.push([ params[i].name, params[i].value ]);
    }

    joms.ajax({
        func: 'apps,ajaxSaveSettings',
        data: [ data ],
        callback: function( json ) {
            if ( json.error ) {
                elem.find('.joms-popup__content').html( json.error );
                elem.find('.joms-popup__action').remove();
                return;
            }

            popup.close();
        }
    });
}

function viewAll() {
    var $ct = elem.find('.joms-popup__content').eq(0),
        height = $ct.innerHeight();

    $ct.css({
        height: height,
        overflow: 'auto'
    });

    elem.find('.joms-js--btn-view-all').parent().remove();
    elem.find('.joms-js--app').show();

    $ct.animate({ scrollTop: $ct[0].scrollHeight });
}

function buildHtml( json, type ) {
    var action = '';

    json || (json = {});

    if ( type === 'setting' && json.btnSave ) {
        action = [
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-js--button-close joms-left">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
            '<button class="joms-button--primary joms-button--small" data-ui-object="popup-button-save">', json.btnSave, '</button>',
            '</div>'
        ].join('');
    } else {
        action = [
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
            '</div>'
        ].join('');
    }

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.html || '' ), '</div>',
        action,
        '</div>'
    ].join('');
}

// Exports.
return function( pos ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, pos );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.app || (joms.popup.app = {});
    joms.popup.app.privacy = factory( root, $ );

    define('popups/app.privacy',[ 'utils/popup' ], function() {
        return joms.popup.app.privacy;
    });

})( window, joms.jQuery, function() {

var popup, elem, name;

function render( _popup, _name ) {
    if ( elem ) elem.off();
    popup = _popup;
    name = _name;

    joms.ajax({
        func: 'apps,ajaxShowPrivacy',
        data: [ name ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '[data-ui-object=popup-button-save]', save );
        }
    });
}

function save() {
    var $radio = elem.find('input[type=radio]:checked'),
        privacy = $radio.val();

    joms.ajax({
        func: 'apps,ajaxSavePrivacy',
        data: [ name, privacy ],
        callback: function( json ) {
            if ( json.error ) {
                elem.find('.joms-popup__content').html( json.error );
                elem.find('.joms-popup__action').remove();
                return;
            }

            popup.close();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.html || '' ), '</div>',
        '<div class="joms-popup__action">',
        '<button class="joms-button--primary joms-button--small" data-ui-object="popup-button-save">', json.btnSave, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( name ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, name );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.app || (joms.popup.app = {});
    joms.popup.app.remove = factory( root, $ );

    define('popups/app.remove',[ 'utils/popup' ], function() {
        return joms.popup.app.remove;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'apps,ajaxRemove',
        data: [ id ],
        callback: function( json ) {
            var $tab, $nexttab, $content;

            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            // locate the app
            $tab = $( '.joms-js--app-tab-' + id );
            $content = $( '#joms-js--app-' + id );

            // find next tab
            $nexttab = $tab.prev();
            if ( !$nexttab.length ) $nexttab = $tab.next().not('.joms-js--app-new');

            // remove the app
            $tab.remove();
            $content.remove();
            $nexttab.length && $nexttab.click();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.html || '' ), '</div>',
        '<div class="joms-popup__action">',
        '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.app || (joms.popup.app = {});
    joms.popup.app.setting = factory( root, $ );

    define('popups/app.setting',[ 'utils/popup' ], function() {
        return joms.popup.app.setting;
    });

})( window, joms.jQuery, function() {

var popup, elem, id, name;

function render( _popup, _id, _name ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;
    name = _name;

    joms.ajax({
        func: 'apps,ajaxShowSettings',
        data: [ id, name ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '[data-ui-object=popup-button-save]', save );
        }
    });
}

function save() {
    var $form = elem.find('form'),
        params = $form.serializeArray(),
        data = [],
        i;

    for ( i = 0; i < params.length; i++ ) {
        data.push([ params[i].name, params[i].value ]);
    }

    joms.ajax({
        func: 'apps,ajaxSaveSettings',
        data: [ data ],
        callback: function( json ) {
            if ( json.error ) {
                elem.find('.joms-popup__content').html( json.error );
                elem.find('.joms-popup__action').remove();
                return;
            }

            popup.close();
        }
    });
}

function buildHtml( json ) {
    var action = '';

    json || (json = {});

    if ( json.btnSave ) {
        action = [
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-js--button-close joms-left">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
            '<button class="joms-button--primary joms-button--small" data-ui-object="popup-button-save">', json.btnSave, '</button>',
            '</div>'
        ].join('');
    }

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content joms-popup__content--single" style="max-height:315px; overflow:auto">', ( json.html || '' ), '</div>',
        action,
        '</div>'
    ].join('');
}

// Exports.
return function( id, name ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id, name );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.app = factory( root, joms.popup.app || {});

    define('popups/app',[
        'popups/app.about',
        'popups/app.browse',
        'popups/app.privacy',
        'popups/app.remove',
        'popups/app.setting'
    ], function() {
        return joms.popup.app;
    });

})( window, function( window, sub ) {

// Exports.
return joms._.extend({}, sub );

});


(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.avatar || (joms.popup.avatar = {});
    joms.popup.avatar.change = factory( root, $ );

    define('popups/avatar.change',[ 'utils/crop', 'utils/loadlib', 'utils/popup' ], function() {
        return joms.popup.avatar.change;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, type, id, uploader, container, button, result;

function render( _popup, _type, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    type = _type;
    id = _id;

    joms.ajax({
        func: 'photos,ajaxUploadAvatar',
        data: [ type, id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-upload', upload );
            elem.on( 'click', '.joms-js--button-save', save );
            elem.on( 'click', '.joms-js--button-rotate-left', rotateLeft );
            elem.on( 'click', '.joms-js--button-rotate-right', rotateRight );

            // Init uploader upon render.
            uploadInit();
        }
    });
}

function save() {
    var crop = joms.util.crop.getSelection();

    joms.ajax({
        func: 'photos,ajaxUpdateThumbnail',
        data: [ type, id, crop.x, crop.y, crop.width, crop.height ],
        callback: function( json ) {
            if ( json.success ) {
                window.location.reload( true );
            }
        }
    });
}

function rotateLeft() {
    joms.api.avatarRotate( type, id, 'left', function() {
        reloadImage();
    });
}

function rotateRight() {
    joms.api.avatarRotate( type, id, 'right', function() {
        reloadImage();
    });
}

function reloadImage() {
    var cropper = $('.joms-avatar__cropper'),
        img = cropper.find('img'),
        src = img.attr('src');

    src = src.replace(/\?.*$/, '');
    src = src + '?_=' + (new Date()).getTime();

    joms.util.crop.detach();
    img.removeAttr('src');
    img.attr( 'src', src );

    setTimeout(function() {
        joms.util.crop( img );
    }, 100 );
}

function upload() {
    uploadInit(function() {
        button.click();
    });
}

function uploadInit( callback ) {
    if ( typeof callback !== 'function' ) {
        callback = function() {};
    }

    if ( uploader ) {
        callback();
        return;
    }

    joms.util.loadLib( 'plupload', function () {
        var url;

        url       = elem.find('form').attr('action');
        container = $('<div id="joms-js--avatar-uploader" aria-hidden="true" style="width:1px; height:1px; position:absolute; overflow:hidden;">').appendTo( document.body );
        button    = $('<div id="joms-js--avatar-uploader-button">').appendTo( container );
        uploader  = new window.plupload.Uploader({
            url: url,
            filters: [{ title: 'Image files', extensions: 'jpg,jpeg,png,gif' }],
            container: 'joms-js--avatar-uploader',
            browse_button: 'joms-js--avatar-uploader-button',
            runtimes: 'html5,html4',
            multi_selection: false,
            file_data_name: 'filedata'
        });

        uploader.bind( 'FilesAdded', uploadAdded );
        uploader.bind( 'Error', uploadError );
        uploader.bind( 'UploadProgress', uploadProgress );
        uploader.bind( 'FileUploaded', uploadUploaded );
        uploader.bind( 'UploadComplete', uploadComplete );
        uploader.init();

        button = container.find('input[type=file]');
        callback();
    });
}

function uploadAdded( up ) {
    window.setTimeout(function() {
        elem.find('.joms-progressbar__progress').css({ width: 0 });
        up.refresh();
        up.start();
    }, 0);
}

function uploadError() {

}

function uploadProgress( up, file ) {
    var percent, bar;

    percent = Math.min( 100, Math.floor( file.loaded / file.size * 100 ) );
    bar = elem.find( '.joms-progressbar__progress' );
    bar.stop().animate({ width: percent + '%' });
}

function uploadUploaded( up, files, data ) {
    var json = {},
        cropper;

    // Parse json response.
    try {
        json = JSON.parse( data.response );
    } catch ( e ) {}

    result = json;

    if ( json.msg && !json.error ) {
        cropper = $('.joms-avatar__cropper');
        cropper.find('img').attr( 'src', json.msg );
        cropper.show();
        setTimeout(function() {
            var elem = cropper.find('img');
            joms.util.crop.detach();
            joms.util.crop( elem );
        }, 100 );
    }
}

function uploadComplete() {
    if ( ! result.error ) {
        elem.find('.joms-js--avatar-uploader-error').hide();
    } else {
        elem.find('.joms-js--avatar-uploader-error').html( result.msg ).show();
        elem.find('.joms-progressbar__progress').stop().animate({ width: '0%' });
    }
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        ( json.html || '' ),
        '<div class="joms-popup__action">',
        '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( type, id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, type, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.avatar || (joms.popup.avatar = {});
    joms.popup.avatar.remove = factory( root, $ );

    define('popups/avatar.remove',[ 'utils/popup' ], function() {
        return joms.popup.avatar.remove;
    });

})( window, joms.jQuery, function() {

var popup, elem, id;

function render( _popup, _type, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'profile,ajaxRemovePicture',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '[data-ui-object=popup-button-cancel]', cancel );
            elem.on( 'click', '[data-ui-object=popup-button-save]', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    elem.find('form').submit();
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.html || '' ), '</div>',
        '<div class="joms-popup__action">',
        '<a href="javascript:" class="joms-button--neutral joms-button--small joms-left" data-ui-object="popup-button-cancel">', json.btnNo, '</a> &nbsp;',
        '<button class="joms-button--primary joms-button--small" data-ui-object="popup-button-save">', json.btnYes, '</button>',
        '<div class="joms-popup__hide">',
        '<form method="POST" action="', json.redirUrl, '"><input type="hidden" name="userid" value="' , id, '"></form>',
        '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( type, id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, type, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.avatar || (joms.popup.avatar = {});
    joms.popup.avatar.rotate = factory( root, $ );

    define('popups/avatar.rotate',[],function() {
        return joms.popup.avatar.rotate;
    });

})( window, joms.jQuery, function( window, $ ) {

function render( type, id, direction, callback ) {
    joms.ajax({
        func: 'profile,ajaxRotateAvatar',
        data: [ type, id, direction ],
        callback: function( json ) {
            if ( json.error ) {
                window.alert( json.error );
                return;
            }

            if ( json.success ) {
                $( '.joms-js--avatar-' + id )
                    .attr( 'src', json.avatar + '?_=' + (new Date()).getTime() );

                if ( typeof callback === 'function' ) {
                    callback( json );
                }
            }
        }
    });
}

// Exports.
return function( type, id, direction, callback ) {
    render( type, id, direction, callback );
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.avatar = factory( root, joms.popup.avatar || {});

    define('popups/avatar',[
        'popups/avatar.change',
        'popups/avatar.remove',
        'popups/avatar.rotate'
    ], function() {
        return joms.popup.avatar;
    });

})( window, function( window, sub ) {

// Exports.
return joms._.extend({}, sub );

});


(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.comment || (joms.popup.comment = {});
    joms.popup.comment.showLikes = factory( root, $ );

    define('popups/comment.showlikes',[ 'utils/popup' ], function() {
        return joms.popup.comment.showLikes;
    });

})( window, joms.jQuery, function() {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'activities,ajaxshowLikedUser',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock joms-popup--rounded joms-popup--80pc">',
        '<button class="mfp-close joms-hide"></button>',
        '<div class="joms-comment">', ( json.html || '' ), '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.comment = factory( root, joms.popup.comment || {});

    define('popups/comment',[
        'popups/comment.showlikes'
    ], function() {
        return joms.popup.comment;
    });

})( window, function( window, sub ) {

// Exports.
return joms._.extend({}, sub );

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.cover || (joms.popup.cover = {});
    joms.popup.cover.change = factory( root, $ );

    define('popups/cover.change',[ 'utils/loadlib', 'utils/popup' ], function() {
        return joms.popup.cover.change;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, type, id, uploader, container, button, result;

function render( _popup, _type, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    type = _type;
    id = _id;

    joms.ajax({
        func: 'photos,ajaxChangeCover',
        data: [ type, id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--album', getPhotoList );
            elem.on( 'click', '.joms-js--back-to-album', backToAlbum );
            elem.on( 'click', '.joms-js--select-photo', selectPhoto );
            elem.on( 'click', '[data-ui-object=popup-button-upload]', upload );

            // Init uploader upon render.
            uploadInit();
        }
    });
}

function getPhotoList() {
    var $el = $( this ),
        album = $el.data('album'),
        total = $el.data('total');

    joms.ajax({
        func: 'photos,ajaxGetPhotoList',
        data: [ album, total ],
        callback: function( json ) {
            if ( json && json.html ) {
                $('.joms-js--album-list').hide();
                $('.joms-js--photo-list').html( json.html ).show();
            }
        }
    });
}

function backToAlbum() {
    $('.joms-js--photo-list').hide();
    $('.joms-js--album-list').show();
}

function selectPhoto() {
    var $el = $( this ),
        photo = $el.data('photo');

    joms.ajax({
        func: 'photos,ajaxSetPhotoCover',
        data: [ type, photo, id ],
        callback: function( json ) {
            if ( json && json.path ) {
                $('.joms-js--cover-image > img')
                    .attr( 'src', json.path )
                    .css({ top: 0 });

                $('.joms-js--cover-image-mobile')
                    .css({ background: 'url(' + json.path + ') no-repeat center center' });

                popup.close();
                $('.joms-js--menu-reposition').show();
            }
        }
    });
}

function upload() {
    uploadInit(function() {
        button.click();
    });
}

function uploadInit( callback ) {
    if ( typeof callback !== 'function' ) {
        callback = function() {};
    }

    if ( uploader ) {
        callback();
        return;
    }

    joms.util.loadLib( 'plupload', function () {
        var url;

        url       = elem.find('form').attr('action');
        container = $('<div id="joms-js--cover-uploader" aria-hidden="true" style="width:1px; height:1px; position:absolute; overflow:hidden;">').appendTo( document.body );
        button    = $('<div id="joms-js--cover-uploader-button">').appendTo( container );
        uploader  = new window.plupload.Uploader({
            url: url,
            filters: [{ title: 'Image files', extensions: 'jpg,jpeg,png,gif' }],
            container: 'joms-js--cover-uploader',
            browse_button: 'joms-js--cover-uploader-button',
            runtimes: 'html5,html4',
            multi_selection: false,
            file_data_name: 'uploadCover'
        });

        uploader.bind( 'FilesAdded', uploadAdded );
        uploader.bind( 'UploadProgress', uploadProgress );
        uploader.bind( 'Error', function() {});
        uploader.bind( 'FileUploaded', uploadUploaded );
        uploader.bind( 'UploadComplete', uploadComplete );
        uploader.init();

        button = container.find('input[type=file]');
        callback();
    });
}

function uploadAdded( up ) {
    window.setTimeout(function() {
        up.refresh();
        up.start();
    }, 0);
}

function uploadProgress( up, file ) {
    var percent, bar;
    percent = Math.min( 100, Math.floor( file.loaded / file.size * 100 ) );
    bar = elem.find( '.joms-progressbar__progress' );
    bar.stop().animate({ width: percent + '%' });
}

function uploadUploaded( up, files, data ) {
    var json = {};

    // Parse json response.
    try {
        json = JSON.parse( data.response );
    } catch ( e ) {}

    result = json;

    if ( json.path ) {
        $('.joms-js--cover-image > img')
            .attr( 'src', json.path )
            .css({ top: 0 });

        $('.joms-js--cover-image-mobile')
            .css({ background: 'url(' + json.path + ') no-repeat center center' });

        popup.close();
        $('.joms-js--menu-reposition').show();
    }
}

function uploadComplete() {
    if ( ! result.error ) {
        elem.find('.joms-js--cover-uploader-error').hide();
    } else {
        elem.find('.joms-js--cover-uploader-error').html( result.error ).show();
        elem.find('.joms-progressbar__progress').stop().animate({ width: '0%' });
    }
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock joms-popup--500">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        ( json.html || '' ),
        '<div class="joms-popup__action">',
        '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( type, id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, type, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.cover || (joms.popup.cover = {});
    joms.popup.cover.remove = factory( root, $ );

    define('popups/cover.remove',[ 'utils/popup' ], function() {
        return joms.popup.cover.remove;
    });

})( window, joms.jQuery, function() {

var popup, elem, type, id;

function render( _popup, _type, _id ) {
    var func, data;

    if ( elem ) elem.off();
    popup = _popup;
    type = _type;
    id = _id;

    func = 'profile,ajaxRemoveCover';
    data = [ id ];

    if ( type === 'group' || type ==='event' ) {
        func = 'photos,ajaxRemoveCover';
        data = [ type, id ];
    }

    joms.ajax({
        func: func,
        data: data,
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    var $form = elem.find('form');
    if ( ! $form.data('saving') ) {
        $form.data( 'saving', 1 );
        $form.submit();
    }
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1', ( json.error ? ' joms-popup__hide' : '' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', ( json.html || '' ), '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnNo, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnYes, '</button>',
            '<div class="joms-popup__hide">',
                '<form method="POST" action="', json.redirUrl, '">',
                    ( type === 'group' || type ==='event' ? '<input type="hidden" name="type" value="' + type + '">' : '' ),
                    ( type === 'group' || type ==='event' ? '<input type="hidden" name="id" value="' + id + '">' : '' ),
                    ( type === 'group' || type ==='event' ? '' : '<input type="hidden" name="userid" value="' + id + '">' ),
                '</form>',
            '</div>',
            '</div>',
        '</div>',
        '<div class="joms-js--step2', ( json.error ? '' : ' joms-popup__hide' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', (json.error || ''), '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( type, id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, type, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.cover = factory( root, joms.popup.cover || {});

    define('popups/cover',[
        'popups/cover.change',
        'popups/cover.remove'
    ], function() {
        return joms.popup.cover;
    });

})( window, function( window, sub ) {

// Exports.
return joms._.extend({}, sub );

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.discussion || (joms.popup.discussion = {});
    joms.popup.discussion.lock = factory( root );

    define('popups/discussion.lock',[ 'utils/popup' ], function() {
        return joms.popup.discussion.lock;
    });

})( window, function() {

var popup, elem, groupid, id;

function render( _popup, _groupid, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    groupid = _groupid;
    id = _id;

    joms.ajax({
        func: 'groups,ajaxShowLockDiscussion',
        data: [ groupid, id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    elem.find('form')[0].submit();
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1">',
            '<div class="joms-popup__content">', json.html, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnNo, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-popup__hide joms-js--step2">',
            '<div class="joms-popup__content joms-popup__content--single"></div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( groupid, id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, groupid, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.discussion || (joms.popup.discussion = {});
    joms.popup.discussion.remove = factory( root );

    define('popups/discussion.remove',[ 'utils/popup' ], function() {
        return joms.popup.discussion.remove;
    });

})( window, function() {

var popup, elem, groupid, id;

function render( _popup, _groupid, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    groupid = _groupid;
    id = _id;

    joms.ajax({
        func: 'groups,ajaxShowRemoveDiscussion',
        data: [ groupid, id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    elem.find('form')[0].submit();
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1">',
            '<div class="joms-popup__content">', json.html, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnNo, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-popup__hide joms-js--step2">',
            '<div class="joms-popup__content joms-popup__content--single"></div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( groupid, id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, groupid, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.discussion = factory( root, joms.popup.discussion || {});

    define('popups/discussion',[
        'popups/discussion.lock',
        'popups/discussion.remove'
    ], function() {
        return joms.popup.discussion;
    });

})( window, function( window, sub ) {

// Exports.
return joms._.extend({}, sub );

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.event || (joms.popup.event = {});
    joms.popup.event['delete'] = factory( root );

    define('popups/event.delete',[ 'utils/popup' ], function() {
        return joms.popup.event['delete'];
    });

})( window, function() {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'events,ajaxWarnEventDeletion',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save( e, step, action ) {
    var data, param;

    if ( step ) {
        data = [ id, step, action ];
    } else  {
        data = [ id, 1 ];
        param = elem.find('[name=recurring]:checked');
        data.push( param && param.length ? param.val() : '' );
    }

    joms.ajax({
        func: 'events,ajaxDeleteEvent',
        data: data,
        callback: function( json ) {
            var $ct;

            elem.find('.joms-js--step1').hide();
            elem.find('.joms-js--step2').show().children().first()
                .append( '<div>' + (json.error || json.message) + '</div>' );

            if ( json.next ) {
                save( null, json.next, data[2] );
            } else if ( json.redirect ) {
                $ct = elem.find('.joms-js--step2');
                $ct.find('.joms-js--button-done')
                    .html( json.btnDone )
                    .on( 'click', function() {
                        window.location = json.redirect;
                    });

                $ct.find('.joms-popup__action').show();
                $ct.find('.joms-popup__content').removeClass('joms-popup__content--single');
            }
        }
    });
}


function buildHtml( json ) {
    var form, rad, i;

    json || (json = {});

    form = '';
    if ( json.radios && json.radios.length ) {
        form  = '<div><form style="margin:5px;padding:0">';
        for ( i = 0; i < json.radios.length; i++ ) {
            rad = json.radios[i];
            form += '<div><label> <input type="radio" name="recurring" value="' + rad[0] + '"' + (rad[2] ? ' checked' : '') + '> ';
            form += rad[1] + '</label></div>';
        }
        form += '</form></div>';
    }

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1', ( json.error ? ' joms-popup__hide' : '' ), '">',
            '<div class="joms-popup__content">', json.html, form, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnCancel, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnDelete, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-js--step2', ( json.error ? '' : ' joms-popup__hide' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', ( json.error || '' ), '</div>',
            '<div class="joms-popup__action joms-popup__hide">',
            '<button class="joms-button--primary joms-js--button-done"></button>',
            '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.event || (joms.popup.event = {});
    joms.popup.event.invite = factory( root, $ );

    define('popups/event.invite',[ 'utils/popup' ], function() {
        return joms.popup.event.invite;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, tabAll, tabSelected, btnSelect, btnLoad, id, type, keyword, start, limit, xhr;

function render( _popup, _id, _type ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;
    type = _type;

    limit = 200;

    joms.ajax({
        func: 'system,ajaxShowInvitationForm',
        data: [ null, '', id, 1, type === 'group' ? 0 : 1, type ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            // Override limit supplied in json.
            if ( json.limit ) {
                limit = +json.limit;
            }

            elem = popup.contentContainer;
            tabAll = elem.find('.joms-tab__content').eq(0);
            tabSelected = elem.find('.joms-tab__content').eq(1);
            btnSelect = elem.find('[data-btn-select]');
            btnLoad = elem.find('[data-btn-load]');

            elem.on( 'keyup', '[data-search]', search );
            elem.on( 'click', '.joms-tab__bar a', changeTab );
            elem.on( 'click', '[data-btn-select]', selectAll );
            elem.on( 'click', '[data-btn-load]', load );
            elem.on( 'click', '[data-btn-save]', save );
            elem.on( 'click', 'input[type=checkbox]', toggle );

            getFriendList('');
        }
    });
}

function search( e ) {
    var elem = $( e.currentTarget );
    getFriendList( elem.val() );
}

function save() {
    var friendsearch = '',
        checkboxes = tabSelected.find('input[type=checkbox]'),
        friends = [],
        emails = elem.find('input[name=emails]').val() || '',
        message = elem.find('[name=message]').val() || '',
        rTrim = /^\s+|\s+$/g,
        params;

    checkboxes.each(function() {
        friends.push( this.value );
    });

    emails = emails.replace( rTrim, '' );
    message = message.replace( rTrim, '' );

    params = [
        [ 'friendsearch', friendsearch ],
        [ 'emails', emails ],
        [ 'message', message ],
        [ 'friends', friends.join(',') ],
    ];

    joms.ajax({
        func: 'system,ajaxSubmitInvitation',
        data: [ 'events,inviteUsers', id, params ],
        callback: function() {
            elem.off();
            popup.close();
        }
    });
}

function changeTab( e ) {
    var $el = $( e.target ),
        selected = $el.attr('href') === '#joms-popup-tab-selected',
        lang = window.joms_lang[ selected ? 'COM_COMMUNITY_UNSELECT_ALL' : 'COM_COMMUNITY_SELECT_ALL' ];

    btnSelect.html( lang );
}

function selectAll() {
    var ct = $('.joms-tab__content:visible'),
        clone;

    // Remove selected.
    if ( ct.attr('id') === 'joms-popup-tab-selected' ) {
        ct.find('.joms-js--friend').remove();
        elem.find('input[type=checkbox]').each(function() {
            this.checked = false;
        });
        return;
    }

    // Add selected.
    clone = ct.find('.joms-js--friend').clone();
    clone.find('input[type=checkbox]').add( ct.find('input[type=checkbox]') ).prop( 'checked', 'checked' );
    ct = elem.find('#joms-popup-tab-selected');
    ct.html( clone );
}

function load() {
    getFriendList();
}

function toggle( e ) {
    var checkbox = $( e.target ),
        ct = checkbox.closest('.joms-tab__content'),
        id, clone;

    // Remove selected.
    if ( ct.attr('id') === 'joms-popup-tab-selected' ) {
        id = checkbox[0].value;
        checkbox.closest('.joms-js--friend').remove();
        elem.find('.joms-js--friend-' + id + ' input[type=checkbox]')[0].checked = false;
        return;
    }

    // Remove selected.
    if ( !checkbox[0].checked ) {
        id = checkbox[0].value;
        elem.find('#joms-popup-tab-selected .joms-js--friend-' + id).remove();
        return;
    }

    // Add selected.
    ct = elem.find('#joms-popup-tab-selected');
    clone = checkbox.closest('.joms-js--friend').clone();
    checkbox = clone.find('input[type=checkbox]');
    checkbox[0].checked = true;
    ct.append( clone );
}

function getFriendList( _keyword ) {
    var isReset = typeof _keyword === 'string',
        func, data;

    if ( isReset ) {
        tabAll.empty();
        start = 0;
        keyword = _keyword;
    } else {
        start += limit;
    }

    func = type === 'group' ? 'system,ajaxLoadGroupEventMembers' : 'system,ajaxLoadFriendsList';

    data = [ keyword, 'events,inviteUsers', id, start, limit ];
    if ( type === 'group' ) {
        data.splice( 1, 1 );
    }

    xhr && xhr.abort();
    xhr = joms.ajax({
        func: func,
        data: data,
        callback: function( json ) {
            var html;

            if ( json.html ) {
                html = $( $.trim( json.html ) );
                html.each(function() {
                    var checkbox = $( this ).find(':checkbox'),
                        value = checkbox.val();

                    if ( tabSelected.find(':checkbox[value=' + value + ']').length ) {
                        checkbox[0].checked = true;
                    }
                });

                tabAll.append( html );
            }

            if ( !isReset ) {
                tabAll[0].scrollTop = tabAll[0].scrollHeight;
            }

            // Toggle load more.
            if ( json.loadMore ) {
                btnSelect.css({ width: '49%', marginRight: '2%' });
                btnLoad.css({ width: '49%' }).html( window.joms_lang.COM_COMMUNITY_INVITE_LOAD_MORE + ' (' + json.moreCount + ')' ).show();
            } else {
                btnLoad.hide();
                btnSelect.css({ width: '100%', marginRight: '0' });
            }
        }
    });
}

function buildHtml( json ) {
    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div data-ui-object="popup-step-1"', ( json.error ? ' class="joms-popup__hide"' : '' ), '>',
            '<div class="joms-popup__content">', json.html, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--primary" data-btn-save="1">', json.btnInvite, '</button>',
            '</div>',
        '</div>',
        '<div data-ui-object="popup-step-2"', ( json.error ? '' : ' class="joms-popup__hide"' ), '>',
            '<div class="joms-popup__content joms-popup__content--single" data-ui-object="popup-message">', (json.error || ''), '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id, type ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id, type );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.event || (joms.popup.event = {});
    joms.popup.event.join = factory( root, $ );

    define('popups/event.join',[ 'utils/popup' ], function() {
        return joms.popup.event.join;
    });

})( window, joms.jQuery, function() {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'events,ajaxRequestInvite',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    elem.find('form')[0].submit();
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1 ', ( json.isMember ? 'joms-popup__hide' : '' ), '">',
            '<div class="joms-popup__content">', json.html, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnNo, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-js--step2 ', ( json.isMember ? '' : 'joms-popup__hide' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', json.html, '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.event || (joms.popup.event = {});
    joms.popup.event.leave = factory( root, $ );

    define('popups/event.leave',[ 'utils/popup' ], function() {
        return joms.popup.event.leave;
    });

})( window, joms.jQuery, function() {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'events,ajaxIgnoreEvent',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    elem.find('form')[0].submit();
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1">',
            '<div class="joms-popup__content">', json.html, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnNo, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-popup__hide joms-js--step2">',
            '<div class="joms-popup__content joms-popup__content--single"></div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.event || (joms.popup.event = {});
    joms.popup.event.response = factory( root, $ );

    define('popups/event.response',[ 'utils/popup' ], function() {
        return joms.popup.event.response;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, id;

function render( _popup, _id, _data ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    popup.items[0] = {
        type: 'inline',
        src: buildHtml( _data )
    };

    popup.updateItemHTML();

    elem = popup.contentContainer;

    elem.on( 'click', 'a[data-value]', save );
}

function save( e ) {
    var value = $( e.currentTarget ).data('value');

    joms.ajax({
        func: 'events,ajaxUpdateStatus',
        data: [ id, value ],
        callback: function() {
            window.location.reload();
        }
    });
}

function buildHtml( data ) {
    var options = '',
        i;

    for ( i = 0; i < data.length; i++ ) {
        options += '<li><a data-value="' + data[i][0] + '" href="javascript:">' + data[i][1] + '</a></li>';
    }

    return [
        '<div class="joms-popup joms-popup--dropdown">',
            '<ul class="joms-dropdown">', options, '</ul>',
            '<button class="mfp-close" type="button" title="Close (Esc)">×</button>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    var data = [].slice.call( arguments );
    data.shift();

    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id, data );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.event || (joms.popup.event = {});
    joms.popup.event.addFeatured = factory( root, $ );

    define('popups/event.addfeatured',[ 'utils/popup' ], function() {
        return joms.popup.event.addFeatured;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'events,ajaxAddFeatured',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.st.callbacks || (popup.st.callbacks = {});
            popup.st.callbacks.close = function() {
                window.location.reload();
            };

            popup.updateItemHTML();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.html || json.error || '' ), '</div>',
        '<div class="joms-popup__action">',
        '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.event || (joms.popup.event = {});
    joms.popup.event.rejectGuest = factory( root, $ );

    define('popups/event.rejectguest',[ 'utils/popup' ], function() {
        return joms.popup.event.rejectGuest;
    });

})( window, joms.jQuery, function() {

var popup, elem, id, userid;

function render( _popup, _id, _userid ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;
    userid = _userid;

    joms.ajax({
        func: 'events,ajaxConfirmRemoveGuest',
        data: [ userid, id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    var checked = elem.find('input:checkbox')[0].checked || false;

    joms.ajax({
        func: checked ? 'events,ajaxBlockGuest' : 'events,ajaxRemoveGuest',
        data: [ userid, id ],
        callback: function( json ) {
            var step1 = elem.find('.joms-js--step1'),
                step2 = elem.find('.joms-js--step2');

            if ( !json.error ) {
                popup.st.callbacks || (popup.st.callbacks = {});
                popup.st.callbacks.close = function() {
                    window.location.reload();
                };
            }

            step2.find('.joms-popup__content').html( json.error || json.message );
            step1.hide();
            step2.show();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1">',
            '<div class="joms-popup__content">', json.html, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnNo, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-popup__hide joms-js--step2">',
            '<div class="joms-popup__content joms-popup__content--single"></div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id, userid ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id, userid );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.event || (joms.popup.event = {});
    joms.popup.event.removeFeatured = factory( root, $ );

    define('popups/event.removefeatured',[ 'utils/popup' ], function() {
        return joms.popup.event.removeFeatured;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'events,ajaxRemoveFeatured',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.st.callbacks || (popup.st.callbacks = {});
            popup.st.callbacks.close = function() {
                window.location.reload();
            };

            popup.updateItemHTML();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.html || json.error || '' ), '</div>',
        '<div class="joms-popup__action">',
        '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.event || (joms.popup.event = {});
    joms.popup.event.report = factory( root );

    define('popups/event.report',[ 'utils/popup' ], function() {
        return joms.popup.event.report;
    });

})( window, function() {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'system,ajaxReport',
        data: [],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'change', 'select', changeText );
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function changeText( e ) {
    elem.find('textarea').val( e.target.value );
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    var rTrim = /^\s+|\s+$/g,
        message;

    message = elem.find('textarea').val();
    message = message.replace( rTrim, '' );

    if ( !message ) {
        elem.find('.joms-js--error').show();
        return;
    }

    elem.find('.joms-js--error').hide();

    joms.ajax({
        func: 'system,ajaxSendReport',
        data: [ 'events,reportEvent', window.location.href, message, id ],
        callback: function( json ) {
            elem.find('.joms-js--step1').hide();
            elem.find('.joms-js--step2').show().children().html( json.error || json.message );
        }
    });
}


function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock joms-popup--500">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1', ( json.error ? ' joms-popup__hide' : '' ), '">',
            json.html,
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnCancel, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnSend, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-js--step2', ( json.error ? '' : ' joms-popup__hide' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', ( json.error || '' ), '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.event || (joms.popup.event = {});
    joms.popup.event.banMember = factory( root, $ );

    define('popups/event.banmember',[ 'utils/popup' ], function() {
        return joms.popup.event.banMember;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, id, userid;

function render( _popup, _id, _userid ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;
    userid = _userid;

    joms.ajax({
        func: 'events,ajaxBanMember',
        data: [ userid, id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            if ( json.success ) {
                popup.st.callbacks || (popup.st.callbacks = {});
                popup.st.callbacks.close = function() {
                    window.location.reload();
                };
            }
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', ( json.title || '' ), '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.html || json.message || json.error || '' ), '</div>',
        '<div class="joms-popup__action">',
        '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id, userid ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id, userid );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.event || (joms.popup.event = {});
    joms.popup.event.unbanMember = factory( root, $ );

    define('popups/event.unbanmember',[ 'utils/popup' ], function() {
        return joms.popup.event.unbanMember;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, id, userid;

function render( _popup, _id, _userid ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;
    userid = _userid;

    joms.ajax({
        func: 'events,ajaxUnbanMember',
        data: [ userid, id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            if ( json.success ) {
                popup.st.callbacks || (popup.st.callbacks = {});
                popup.st.callbacks.close = function() {
                    window.location.reload();
                };
            }
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', ( json.title || '' ), '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.html || json.message || json.error || '' ), '</div>',
        '<div class="joms-popup__action">',
        '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id, userid ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id, userid );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.event || (joms.popup.event = {});
    joms.popup.event.unpublish = factory( root );

    define('popups/event.unpublish',[ 'utils/popup' ], function() {
        return joms.popup.event.unpublish;
    });

})( window, function() {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'events,ajaxShowUnpublishEvent',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save( e, step, action ) {
    var data, param;

    if ( step ) {
        data = [ id, step, action ];
    } else  {
        data = [ id ];
    }
    
    elem.find('.joms-js--step1').find('.joms-popup__content').html(joms_lang.COM_COMMUNITY_POPUP_LOADING);
    elem.find('.joms-js--step1').find('.joms-popup__action').children().hide();
    
    joms.ajax({
        func: 'events,ajaxUnpublishEvent',
        data: data,
        callback: function( json ) {
            var $ct;

            elem.find('.joms-js--step1').hide();
            elem.find('.joms-js--step2').show().children().first()
                .append( '<div>' + (json.error || json.message) + '</div>' );

            if ( json.next ) {
                save( null, json.next, data[2] );
            } else if ( json.redirect ) {
                $ct = elem.find('.joms-js--step2');
                $ct.find('.joms-js--button-done')
                    .html( json.btnDone )
                    .on( 'click', function() {
                        window.location = json.redirect;
                    });

                $ct.find('.joms-popup__action').show();
                $ct.find('.joms-popup__content').removeClass('joms-popup__content--single');
            }
        }
    });
}


function buildHtml( json ) {
    var form, rad, i;

    json || (json = {});

    form = '';
    if ( json.radios && json.radios.length ) {
        form  = '<div><form style="margin:5px;padding:0">';
        for ( i = 0; i < json.radios.length; i++ ) {
            rad = json.radios[i];
            form += '<div><label> <input type="radio" name="recurring" value="' + rad[0] + '"' + (rad[2] ? ' checked' : '') + '> ';
            form += rad[1] + '</label></div>';
        }
        form += '</form></div>';
    }

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1', ( json.error ? ' joms-popup__hide' : '' ), '">',
            '<div class="joms-popup__content">', json.html, form, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnNo, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-js--step2', ( json.error ? '' : ' joms-popup__hide' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', ( json.error || '' ), '</div>',
            '<div class="joms-popup__action joms-popup__hide">',
            '<button class="joms-button--primary joms-js--button-done"></button>',
            '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.event = factory( root, joms.popup.event || {});

    define('popups/event',[
        'popups/event.delete',
        'popups/event.invite',
        'popups/event.join',
        'popups/event.leave',
        'popups/event.response',
        'popups/event.addfeatured',
        'popups/event.rejectguest',
        'popups/event.removefeatured',
        'popups/event.report',
        'popups/event.banmember',
        'popups/event.unbanmember',
        'popups/event.unpublish'
    ], function() {
        return joms.popup.event;
    });

})( window, function( window, sub ) {

// Exports.
return joms._.extend({}, sub );

});


(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.fbc || (joms.popup.fbc = {});
    joms.popup.fbc.update = factory( root, $ );

    define('popups/fbc.update',[ 'utils/popup' ], function() {
        return joms.popup.fbc.update;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, lang, isMember;

function render( _popup ) {
    if ( elem ) elem.off();
    popup = _popup;

    if ( !window.joms_use_tfa ) {
        update();
    } else {
        popup.items[0] = {
            type: 'inline',
            src: buildTfaDialog()
        };

        popup.updateItemHTML();

        elem = popup.contentContainer;
        elem.off('click');

        elem.on( 'click', '.joms-js--button-next', function() {
            update( elem.find('[name=secret]').val() );
        });

        elem.on( 'click', '.joms-js--button-skip', function() {
            update();
        });
    }
}

function update( secret ) {
    if ( elem ) elem.off();

    joms.ajax({
        func: 'connect,ajaxUpdate',
        data: [ secret || '' ],
        callback: function( json ) {
            var isLoggedIn = json.jax_token_var;

            if ( isLoggedIn ) {
                json.btnNext = json.btnContinue;
                window.jax_token_var = json.jax_token_var;
            }

            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            lang = json.lang;

            elem.off('click');
            elem.on( 'click', '.joms-js--button-next', isLoggedIn ? importData : next );
            elem.on( 'click', '.joms-js--button-back2', back2 );
            elem.on( 'click', '.joms-js--button-next2', next2 );
            elem.on( 'click', '.joms-js--button-back3', back3 );
        }
    });
}

function next() {
    var tnc, error;

    isMember = +elem.find('[name=membertype]:checked').val() === 2;

    if ( isMember ) {
        connectMember();
    } else {
        tnc = elem.find('#joms-js--fbc-tnc-checkbox');
        if ( !tnc.length ) {
            connectNewUser();
        } else {
            tnc = tnc[0];
            error = elem.find('.joms-js--fbc-tnc-error');
            if ( tnc.checked ) {
                error.hide();
                connectNewUser();
            } else {
                error.show();
            }
        }
    }
}

function back2() {
    elem.find('.joms-js--step2').hide();
    elem.find('.joms-js--step3').hide();
    elem.find('.joms-js--step1').show();
}

function next2() {
    if ( isMember ) {
        validateMember();
    } else {
        validateNewUser();
    }
}

function connectNewUser() {
    joms.ajax({
        func: 'connect,ajaxShowNewUserForm',
        data: [ '' ],
        callback: function( json ) {
            var div;

            elem.find('.joms-js--step1').hide();

            div = elem.find('.joms-js--step2');
            div.find('.joms-popup__content').html( json.html );
            div.find('.joms-js--button-back2').html( json.btnBack );
            div.find('.joms-js--button-next2').html( json.btnCreate );
            div.show();
        }
    });
}

function connectMember() {
    joms.ajax({
        func: 'connect,ajaxShowExistingUserForm',
        data: [ '' ],
        callback: function( json ) {
            var div;

            elem.find('.joms-js--step1').hide();

            div = elem.find('.joms-js--step2');
            div.find('.joms-popup__content').html( json.html );
            div.find('.joms-js--button-back2').html( json.btnBack );
            div.find('.joms-js--button-next2').html( json.btnLogin );
            div.show();
        }
    });
}

function validateNewUser() {
    var div = elem.find('.joms-js--step2'),
        name = div.find('[name=name]').val(),
        user = div.find('[name=username]').val(),
        email = div.find('[name=email]').val(),
        types = div.find('[name=profiletype]'),
        profileType = '',
        type;

    if ( types.length ) {
        type = types.filter(':checked');
        if ( !type.length ) {
            div.hide();
            div = elem.find('.joms-js--step3');
            div.find('.joms-popup__content').html( lang.selectProfileType );
            div.find('.joms-js--button-back3').html( lang.btnBack );
            div.show();
            return;
        }
        profileType = types.filter(':checked').val();
    }

    joms.ajax({
        func: 'connect,ajaxCreateNewAccount',
        data: [ name, user, email, profileType ],
        callback: function( json ) {
            var div;

            if ( json.error ) {
                elem.find('.joms-js--step2').hide();

                div = elem.find('.joms-js--step3');
                div.find('.joms-popup__content').html( json.error );
                div.find('.joms-js--button-back3').html( json.btnBack );
                div.show();
                return;
            }

            elem.off();
            popup.close();
            joms.popup.fbc.update();
        }
    });
}

function validateMember() {
    var div = elem.find('.joms-js--step2'),
        user = div.find('[name=username]').val(),
        pass = div.find('[name=password]').val();

    joms.ajax({
        func: 'connect,ajaxValidateLogin',
        data: [ user, pass ],
        callback: function( json ) {
            var div;

            if ( json.error ) {
                elem.find('.joms-js--step2').hide();

                div = elem.find('.joms-js--step3');
                div.find('.joms-popup__content').html( json.error );
                div.find('.joms-js--button-back3').html( json.btnBack );
                div.show();
                return;
            }

            elem.off();
            popup.close();
            joms.popup.fbc.update();
        }
    });
}

// function checkName( name ) {
//     joms.ajax({
//         func: 'connect,ajaxCheckName',
//         data: [ name ],
//         callback: function( json ) {
//         }
//     });
// }

// function checkUsername( username ) {
//     joms.ajax({
//         func: 'connect,ajaxCheckUsername',
//         data: [ username ],
//         callback: function( json ) {
//         }
//     });
// }

// function checkEmail( email ) {
//     joms.ajax({
//         func: 'connect,ajaxCheckEmail',
//         data: [ email ],
//         callback: function( json ) {
//         }
//     });
// }

function importData() {
    var status = elem.find('[name=importstatus]'),
        avatar = elem.find('[name=importavatar]');

    status = status.length && status[0].checked ? 1 : 0;
    avatar = avatar.length && avatar[0].checked ? 1 : 0;

    joms.ajax({
        func: 'connect,ajaxImportData',
        data: [ status, avatar ],
        callback: function( json ) {
            var div;

            elem.find('.joms-js--step1').hide();

            if ( json.error ) {
                elem.off('click').on( 'click', '.joms-js--button-next2', cancel );

                div = elem.find('.joms-js--step2');
                div.find('.joms-popup__content').html( json.error );
                div.find('.joms-js--button-back2').hide();
                div.find('.joms-js--button-next2').html( json.btnNext );
                div.show();
                return;
            }

            if ( !json.btnUpdate ) {
                cancel();
                window.location = json.redirect;
                return;
            }

            elem.off('click').on( 'click', '.joms-js--button-back2', cancel );
            elem.off('click').on( 'click', '.joms-js--button-next2', function() {
                window.location = json.redirect;
            });

            div = elem.find('.joms-js--step2');
            div.find('.joms-popup__content').html( json.html );
            div.find('.joms-js--button-back2').html( json.btnSkip );
            div.find('.joms-js--button-next2').html( json.btnUpdate );
            div.show();
        }
    });
}

function back3() {
    elem.find('.joms-js--step3').hide();
    if ( isMember ) {
        elem.find('.joms-js--step2').hide();
        elem.find('.joms-js--step1').show();
    } else {
        elem.find('.joms-js--step2').show();
        elem.find('.joms-js--step1').hide();
    }
}

function cancel() {
    elem.off();
    popup.close();
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1">',
            '<div class="joms-popup__content ', ( json.btnNext ? '' : 'joms-popup__content--single' ), '">', ( json.error || json.html || '' ), '</div>',
            ( json.btnNext ? '<div class="joms-popup__action">' : '' ),
            ( json.btnNext ? '<button class="joms-button--primary joms-button--small joms-js--button-next">' + json.btnNext + '</button>' : '' ),
            ( json.btnNext ? '</div>' : '' ),
        '</div>',
        '<div class="joms-js--step2 joms-popup__hide">',
            '<div class="joms-popup__content"></div>',
            '<div class="joms-popup__action">',
                '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-back2"></button>',
                '<button class="joms-button--primary joms-button--small joms-js--button-next2"></button>',
            '</div>',
        '</div>',
        '<div class="joms-js--step3 joms-popup__hide">',
            '<div class="joms-popup__content joms-popup__content--single"></div>',
            '<div class="joms-popup__action">',
                '<button class="joms-button--neutral joms-button--small joms-js--button-back3"></button>',
            '</div>',
        '</div>',
        '</div>'
    ].join('');
}

function buildTfaDialog() {
    var lang = window.joms_lang || {};

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', (lang.COM_COMMUNITY_AUTHENTICATION_KEY || 'Authentication key'), '</div>',
        '<div class="joms-popup__content">',
            '<span>', (lang.COM_COMMUNITY_AUTHENTICATION_KEY_LABEL || 'Insert your two-factor authentication key'), '</span>',
            '<input type="text" class="joms-input" name="secret">',
        '</div>',
        '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-skip">', (lang.COM_COMMUNITY_SKIP_BUTTON || 'Skip'), '</button>',
            '<button class="joms-button--primary joms-button--small joms-js--button-next">', (lang.COM_COMMUNITY_NEXT || 'Next'), '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function() {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.fbc = factory( root, joms.popup.fbc || {});

    define('popups/fbc',[
        'popups/fbc.update'
    ], function() {
        return joms.popup.fbc;
    });

})( window, function( window, sub ) {

// Exports.
return joms._.extend({}, sub );

});


(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.file || (joms.popup.file = {});
    joms.popup.file.download = factory( root, $ );

    define('popups/file.download',[ 'utils/popup' ], function() {
        return joms.popup.file.download;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, type, id, path;

function render( _popup, _type, _id, _path ) {
    if ( elem ) elem.off();
    popup = _popup;
    type = _type;
    id = _id;
    path = _path;

    joms.ajax({
        func: 'files,ajaxFileDownload',
        data: [ type, id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            if ( json.url ) {
                popup.close();
                window.open( path );
            }
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', (json.message || json.error), '</div>',
        '<div class="joms-popup__action">',
        '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( type, id, path ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, type, id, path );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.file || (joms.popup.file = {});
    joms.popup.file.list = factory( root, $ );

    define('popups/file.list',[ 'utils/loadlib', 'utils/popup' ], function() {
        return joms.popup.file.list;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, type, id, tab, start, $currentTab, $btnLoadMore;

function render( _popup, _type, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    type = _type;
    id = _id;

    joms.ajax({
        func: 'files,ajaxviewMore',
        data: [ type, id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            $btnLoadMore = elem.find('.joms-js--btn-loadmore');

            elem.on( 'click', '.joms-js--tab-bar a', load );
            elem.on( 'click', '.joms-js--btn-loadmore', loadmore );

            tab = false;
            elem.find('.joms-js--tab-bar a.active').trigger('click');
        }
    });
}

function _load( tabid, callback ) {
    joms.ajax({
        func: 'files,ajaxgetFileList',
        data: [ tabid, id, start, 4, type ],
        callback: function( json ) {
            callback( json );
        }
    });
}

function load() {
    var $tab = $( this ),
        tabid = $tab.data('id');

    if ( tab === tabid ) {
        return;
    }

    tab = tabid;
    $currentTab = elem.find( '.joms-js--tab-' + tabid );

    $tab.addClass('active').siblings().removeClass('active');
    $btnLoadMore.css({ visibility: 'hidden' });
    $currentTab.empty().show().siblings('.joms-js--tab').hide();

    start = 0;
    _load( tab, function( json ) {
        $currentTab.html( json.html );
        if ( json.next && json.count ) {
            start = json.next;
            $btnLoadMore.css({ visibility: 'visible' });
            $btnLoadMore.html( window.joms_lang.COM_COMMUNITY_FILES_LOAD_MORE + ' (' + json.count + ')' );
        } else {
            $btnLoadMore.css({ visibility: 'hidden' });
        }
    });
}

function loadmore() {
    _load( tab, function( json ) {
        $currentTab.append( json.html );
        $currentTab[0].scrollTop = $currentTab[0].scrollHeight;
        if ( json.next && json.count ) {
            start = json.next;
            $btnLoadMore.css({ visibility: 'visible' });
            $btnLoadMore.html( window.joms_lang.COM_COMMUNITY_FILES_LOAD_MORE + ' (' + json.count + ')' );
        } else {
            $btnLoadMore.css({ visibility: 'hidden' });
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock joms-popup--600">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        json.html,
        '</div>'
    ].join('');
}

// Exports.
return function( type, id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, type, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.file || (joms.popup.file = {});
    joms.popup.file.remove = factory( root, $ );

    define('popups/file.remove',[ 'utils/popup' ], function() {
        return joms.popup.file.remove;
    });

})( window, joms.jQuery, function( window, $ ) {

var type, id;

function render( popup, json ) {
    popup.items[0] = {
        type: 'inline',
        src: buildHtml( json )
    };

    popup.updateItemHTML();
}

function _delete( _type, _id ) {
    type = _type;
    id = _id;

    if ( window.confirm( window.joms_lang.COM_COMMUNITY_FILES_DELETE_CONFIRM ) ) {
        joms.ajax({
            func: 'files,ajaxDeleteFile',
            data: [ type, id ],
            callback: function( json ) {
                if ( json.success ) {
                    $( '.joms-js--file-' + id ).remove();
                    return;
                }

                joms.util.popup.prepare(function( mfp ) {
                    render( mfp, json );
                });
            }
        });
    }
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button> &nbsp; </div>',
        '<div class="joms-popup__content joms-popup__content--single">', (json.message || json.error), '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( type, id ) {
    _delete( type, id );
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.file || (joms.popup.file = {});
    joms.popup.file.upload = factory( root, $ );

    define('popups/file.upload',[ 'utils/loadlib', 'utils/popup' ], function() {
        return joms.popup.file.upload;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, type, id, uploader, uploaderUrl, uploaderButton, uploaderPreview, doReload;

function render( _popup, _type, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    type = _type;
    id = _id;

    joms.ajax({
        func: 'files,ajaxFileUploadForm',
        data: [ type, id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            popup.st.callbacks || (popup.st.callbacks = {});
            popup.st.callbacks.close = function() {
                doReload && window.location.reload();
            };

            elem = popup.contentContainer;
            doReload = false;
            uploaderPreview = elem.find('.joms-js--upload-preview');

            elem.on( 'click', '.joms-js--btn-add', upload );
            elem.on( 'click', '.joms-js--btn-upload', uploadStart );
            elem.on( 'click', '.joms-js--btn-done', function() {
                doReload && window.location.reload();
            });

            // Init uploader upon render.
            uploadInit();
        }
    });
}

function upload() {
    uploadInit(function() {
        uploaderButton.click();
    });
}

function uploadInit( callback ) {
    if ( typeof callback !== 'function' ) {
        callback = function() {};
    }

    if ( uploader ) {
        callback();
        return;
    }

    uploaderUrl = joms.BASE_URL + elem.find('input[name=url]').val();

    joms.util.loadLib( 'plupload', function () {
        var container, button;

        container = $('<div id="joms-js--file-uploader" aria-hidden="true" style="width:1px; height:1px; overflow:hidden">').appendTo( document.body );
        button    = $('<button id="joms-js--file-uploader-button">').appendTo( container );
        uploader  = new window.plupload.Uploader({
            url: uploaderUrl,
            container: 'joms-js--file-uploader',
            browse_button: 'joms-js--file-uploader-button',
            runtimes: 'html5,html4',
            filters: [{ title: 'Document files', extensions: elem.find('input[name=filetype]').val() }],
            max_file_size: elem.find('input[name=maxfilesize]').val() + 'mb'
        });

        uploader.bind( 'FilesAdded', uploadAdded );
        uploader.bind( 'Error', uploadError );
        uploader.bind( 'UploadProgress', uploadProgress );
        uploader.bind( 'FileUploaded', uploadUploaded );
        uploader.bind( 'uploadComplete', uploadComplete );
        uploader.init();

        uploaderButton = container.find('input[type=file]');
        callback();
    });
}

function uploadAdded( up, files ) {
    var html = '',
        i;

    for ( i = 0; i < files.length; i++ ) {
        html += '<div class="joms-file--' + files[i].id + '" style="margin-bottom:5px">';
        html += '<div><strong>' + files[i].name + '</strong> <span>(' + Math.round( files[i].size / 1024 ) + ' KB)</span></div>';
        html += '<div class="joms-progressbar"><div class="joms-progressbar__progress" style="width:0%"></div></div>';
        html += '</div>';
    }

    uploaderPreview.find('.joms-js--upload-placeholder').remove();
    uploaderPreview.append( html );

    elem.find('.joms-js--btn-add').html( elem.find('.joms-js--btn-add').data('lang-more') ).css({ visibility: 'visible' });
    elem.find('.joms-js--btn-upload').show();
    elem.find('.joms-js--btn-done').hide();
}

function uploadError( up, info ) {
    uploaderPreview.find( '.joms-file--' + info.file.id ).remove();
    window.alert( info.message + ' (' + info.code + ')' );
}

function uploadStart() {
    elem.find('.joms-js--btn-add').css({ visibility: 'hidden' });
    elem.find('.joms-js--btn-upload').hide();
    elem.find('.joms-js--btn-done').hide();
    uploader.settings.url = uploaderUrl + '&type=' + type + '&id=' + id;
    uploader.refresh();
    uploader.start();
}

function uploadProgress( up, file ) {
    var percent, bar;
    percent = Math.min( 100, Math.floor( file.loaded / file.size * 100 ) );
    bar = elem.find( '.joms-file--' + file.id );
    bar = bar.find( '.joms-progressbar__progress' );
    bar.stop().animate({ width: percent + '%' });
}

function uploadUploaded( up, file, resp ) {
    var json = {},
        item;

    try {
        json = JSON.parse( resp.response );
    } catch (e) {}

    if ( json.error ) {
        uploader.stop();
        elem.find('.joms-js--btn-add').css({ visibility: 'hidden' });
        elem.find('.joms-js--btn-upload').hide();
        elem.find('.joms-js--btn-done').show();
        elem.find( '.joms-file--' + file.id ).nextAll().andSelf().remove();
        window.alert( json.msg );
        return;
    }

    if ( json.msg ) {
        item = elem.find( '.joms-file--' + file.id );
        item.css({ color: '#F00' });
    } else if ( json.id ) {
        doReload = true;
    }
}

function uploadComplete() {
    elem.find('.joms-js--btn-add').css({ visibility: 'visible' });
    elem.find('.joms-js--btn-upload').hide();
    elem.find('.joms-js--btn-done').show();
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock joms-popup--500">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        json.html,
        '</div>'
    ].join('');
}

// Exports.
return function( type, id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, type, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.file || (joms.popup.file = {});
    joms.popup.file.updateHit = factory( root );

    define('popups/file.updatehit',[ 'utils/popup' ], function() {
        return joms.popup.file.updateHit;
    });

})( window, function( window ) {

return function( id, location ) {
    joms.ajax({
        func: 'files,ajaxUpdateHit',
        data: [ id ],
        callback: function() {}
    });

    if ( typeof location === 'string' ) {
        window.open( location );
    }
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.file = factory( root, joms.popup.file || {});

    define('popups/file',[
        'popups/file.download',
        'popups/file.list',
        'popups/file.remove',
        'popups/file.upload',
        'popups/file.updatehit'
    ], function() {
        return joms.popup.file;
    });

})( window, function( window, sub ) {

// Exports.
return joms._.extend({}, sub );

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.friend || (joms.popup.friend = {});
    joms.popup.friend.add = factory( root );

    define('popups/friend.add',[ 'utils/popup' ], function() {
        return joms.popup.friend.add;
    });

})( window, function() {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'friends,ajaxConnect',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '[data-ui-object=popup-button-cancel]', cancel );
            elem.on( 'click', '[data-ui-object=popup-button-save]', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    var message = elem.find('textarea').val()
        .replace( /\t/g, '\\t' )
        .replace( /\n/g, '\\n' )
        .replace( /&quot;/g,  '"' );

    joms.ajax({
        func: 'friends,ajaxSaveFriend',
        data: [[[ 'msg', message ], [ 'userid', id ]]],
        callback: function( json ) {
            var step1 = elem.find('[data-ui-object=popup-step-1]'),
                step2 = elem.find('[data-ui-object=popup-step-2]');

            if ( !json.error ) {
                popup.st.callbacks || (popup.st.callbacks = {});
                popup.st.callbacks.close = function() {
                    window.location.reload();
                };
            }

            step2.find('[data-ui-object=popup-message]').html( json.error || json.message );
            step1.hide();
            step2.show();
        }
    });
}


function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div data-ui-object="popup-step-1"', ( json.error ? ' class="joms-popup__hide"' : '' ), '>',
            '<div class="joms-popup__content">',
                '<div class="joms-stream__header" style="padding:0">',
                    '<div class="joms-avatar--stream"><img src="', json.avatar, '"></div>',
                    '<div class="joms-stream__meta"><span>', json.desc, '</span></div>',
                '</div>',
            '</div>',
            '<div class="joms-popup__content">',
                '<textarea class="joms-textarea" style="margin:0">', json.message, '</textarea>',
            '</div>',
            '<div class="joms-popup__action">',
                '<a href="javascript:" class="joms-button--neutral joms-button--small joms-left" data-ui-object="popup-button-cancel">', json.btnCancel, '</a> &nbsp;',
                '<button class="joms-button--primary joms-button--small" data-ui-object="popup-button-save">', json.btnAdd, '</button>',
            '</div>',
        '</div>',
        '<div data-ui-object="popup-step-2"', ( json.error ? '' : ' class="joms-popup__hide"' ), '>',
            '<div class="joms-popup__content joms-popup__content--single" data-ui-object="popup-message">', (json.error || ''), '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
            '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.friend || (joms.popup.friend = {});
    joms.popup.friend.addCancel = factory( root );

    define('popups/friend.addcancel',[ 'utils/popup' ], function() {
        return joms.popup.friend.addCancel;
    });

})( window, function() {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'friends,ajaxCancelRequest',
        data: [ id, window.location.href ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    elem.find('form')[0].submit();
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1">',
            '<div class="joms-popup__content">', json.html, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnNo, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-popup__hide joms-js--step2">',
            '<div class="joms-popup__content joms-popup__content--single"></div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.friend || (joms.popup.friend = {});
    joms.popup.friend.approve = factory( root, $ );

    define('popups/friend.approve',[ 'utils/popup', 'functions/notification' ], function() {
        return joms.popup.friend.approve;
    });

})( window, joms.jQuery, function( window, $ ) {

var id;

function render( _id ) {
    id = _id;

    joms.ajax({
        func: 'friends,ajaxApproveRequest',
        data: [ id ],
        callback: function( json ) {
            if ( json.success ) {
                update( json );
                return;
            }

            // On error response.
            joms.util.popup.prepare(function( mfp ) {
                mfp.items[0] = {
                    type: 'inline',
                    src: buildErrorHtml( json )
                };

                mfp.updateItemHTML();
            });
        }
    });
}

function update( json ) {
    $( '.joms-js--frequest-msg-' + id ).html( json.message );
    $( '.joms-js--frequest-btn-' + id ).remove();
    joms.fn.notification.updateCounter( 'frequest', id, -1 );
}

function buildErrorHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.error || json.message ), '</div>',
        '<div class="joms-popup__action">',
        '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    render( id );
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.friend || (joms.popup.friend = {});
    joms.popup.friend.reject = factory( root, $ );

    define('popups/friend.reject',[ 'utils/popup', 'functions/notification' ], function() {
        return joms.popup.friend.reject;
    });

})( window, joms.jQuery, function( window, $ ) {

var id;

function render( _id ) {
    id = _id;

    joms.ajax({
        func: 'friends,ajaxRejectRequest',
        data: [ id ],
        callback: function( json ) {
            if ( json.success ) {
                update( json );
                return;
            }

            // On error response.
            joms.util.popup.prepare(function( mfp ) {
                mfp.items[0] = {
                    type: 'inline',
                    src: buildErrorHtml( json )
                };

                mfp.updateItemHTML();
            });
        }
    });
}

function update( json ) {
    $( '.joms-js--frequest-msg-' + id ).html( json.message );
    $( '.joms-js--frequest-btn-' + id ).remove();
    joms.fn.notification.updateCounter( 'frequest', id, -1 );
}

function buildErrorHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.error || json.message ), '</div>',
        '<div class="joms-popup__action">',
        '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    render( id );
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.friend || (joms.popup.friend = {});
    joms.popup.friend.remove = factory( root, $ );

    define('popups/friend.remove',[ 'utils/popup' ], function() {
        return joms.popup.friend.remove;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'friends,ajaxConfirmFriendRemoval',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.st.callbacks || (popup.st.callbacks = {});
            popup.st.callbacks.close = function() {
                window.location.reload();
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '[data-ui-object=popup-button-cancel]', cancel );
            elem.on( 'click', '[data-ui-object=popup-button-save]', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    var checkbox = elem.find('input[type=checkbox]'),
        func;

    if ( checkbox[0].checked ) {
        func = 'friends,ajaxBlockFriend';
    } else {
        func = 'friends,ajaxRemoveFriend';
    }

    joms.ajax({
        func: func,
        data: [ id ],
        callback: function( json ) {
            var step1 = elem.find('[data-ui-object=popup-step-1]'),
                step2 = elem.find('[data-ui-object=popup-step-2]');

            step2.find('[data-ui-object=popup-message]').html( json.error || json.message );
            step1.hide();
            step2.show();

            if ( json && json.success ) {
                $( '#friend-' + id ).remove();
            }
        }
    });
}


function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div data-ui-object="popup-step-1"', ( json.error ? ' class="joms-popup__hide"' : '' ), '>',
            '<div class="joms-popup__content">', ( json.html || '' ), '</div>',
            '<div class="joms-popup__action">',
            '<a href="javascript:" class="joms-button--neutral joms-button--small joms-left" data-ui-object="popup-button-cancel">', json.btnNo, '</a> &nbsp;',
            '<button class="joms-button--primary joms-button--small" data-ui-object="popup-button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '<div data-ui-object="popup-step-2"', ( json.error ? '' : ' class="joms-popup__hide"' ), '>',
            '<div class="joms-popup__content joms-popup__content--single" data-ui-object="popup-message">', (json.error || ''), '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.friend || (joms.popup.friend = {});
    joms.popup.friend.response = factory( root );

    define('popups/friend.response',[ 'utils/popup', 'functions/notification' ], function() {
        return joms.popup.friend.response;
    });

})( window, function() {

var popup, elem, id, connection;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'friends,ajaxConnect',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            connection = json.connection_id;

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', reject );
            elem.on( 'click', '.joms-js--button-save', approve );
        }
    });
}

function reject() {
    joms.ajax({
        func: 'friends,ajaxRejectRequest',
        data: [ connection ],
        callback: function( json ) {
            update( json );
        }
    });
}

function approve() {
    joms.ajax({
        func: 'friends,ajaxApproveRequest',
        data: [ connection ],
        callback: function( json ) {
            update( json );
        }
    });
}

function update( json ) {
    var step1 = elem.find('.joms-js--step1'),
        step2 = elem.find('.joms-js--step2');

    if ( !json.error ) {
        popup.st.callbacks || (popup.st.callbacks = {});
        popup.st.callbacks.close = function() {
            window.location.reload();
        };
    }

    step2.find('.joms-popup__content').html( json.error || json.message );
    step1.hide();
    step2.show();
}

function buildHtml( json ) {
    var error = false;

    json || (json = {});
    if ( json.error && !json.desc ) {
        error = json.error;
    }

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1', ( error ? ' joms-popup__hide' : '' ), '">',
            '<div class="joms-popup__content">',
                '<div class="joms-stream__header" style="padding:0">',
                    '<div class="joms-avatar--stream"><img src="', json.avatar, '"></div>',
                    '<div class="joms-stream__meta"><span>', json.desc, '</span></div>',
                '</div>',
            '</div>',
            '<div class="joms-popup__content">',
                '<div class="cStream-Quote">', json.message, '</div>',
            '</div>',
            '<div class="joms-popup__action">',
                '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnReject, '</button>',
                '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnAccept, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-js--step2', ( error ? '' : ' joms-popup__hide' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', ( error || '' ), '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.friend = factory( root, joms.popup.friend || {});

    define('popups/friend',[
        'popups/friend.add',
        'popups/friend.addcancel',
        'popups/friend.approve',
        'popups/friend.reject',
        'popups/friend.remove',
        'popups/friend.response'
    ], function() {
        return joms.popup.friend;
    });

})( window, function( window, sub ) {

// Exports.
return joms._.extend({}, sub );

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.group || (joms.popup.group = {});
    joms.popup.group['delete'] = factory( root );

    define('popups/group.delete',[ 'utils/popup' ], function() {
        return joms.popup.group['delete'];
    });

})( window, function() {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'groups,ajaxWarnGroupDeletion',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save( e, step ) {
    joms.ajax({
        func: 'groups,ajaxDeleteGroup',
        data: [ id, step || 1 ],
        callback: function( json ) {
            var $ct;

            elem.find('.joms-js--step1').hide();
            elem.find('.joms-js--step2').show().children().first()
                .append( '<div>' + (json.error || json.message) + '</div>' );

            if ( json.next ) {
                save( null, json.next );
            } else if ( json.redirect ) {
                $ct = elem.find('.joms-js--step2');
                $ct.find('.joms-js--button-done')
                    .html( json.btnDone )
                    .on( 'click', function() {
                        window.location = json.redirect;
                    });

                $ct.find('.joms-popup__action').show();
                $ct.find('.joms-popup__content').removeClass('joms-popup__content--single');
            }
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1', ( json.error ? ' joms-popup__hide' : '' ), '">',
            '<div class="joms-popup__content">', json.html, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnCancel, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnDelete, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-js--step2', ( json.error ? '' : ' joms-popup__hide' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', ( json.error || '' ), '</div>',
            '<div class="joms-popup__action joms-popup__hide">',
            '<button class="joms-button--primary joms-js--button-done"></button>',
            '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.group || (joms.popup.group = {});
    joms.popup.group.invite = factory( root, $ );

    define('popups/group.invite',[ 'utils/popup' ], function() {
        return joms.popup.group.invite;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, tabAll, tabSelected, btnSelect, btnLoad, id, keyword, start, limit, xhr;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    limit = 200;

    joms.ajax({
        func: 'system,ajaxShowInvitationForm',
        data: [ null, '', id, 1, 1 ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            // Override limit supplied in json.
            if ( json.limit ) {
                limit = +json.limit;
            }

            elem = popup.contentContainer;
            tabAll = elem.find('.joms-tab__content').eq(0);
            tabSelected = elem.find('.joms-tab__content').eq(1);
            btnSelect = elem.find('[data-btn-select]');
            btnLoad = elem.find('[data-btn-load]');

            elem.on( 'keyup', '[data-search]', search );
            elem.on( 'click', '.joms-tab__bar a', changeTab );
            elem.on( 'click', '[data-btn-select]', selectAll );
            elem.on( 'click', '[data-btn-load]', load );
            elem.on( 'click', '[data-btn-save]', save );
            elem.on( 'click', 'input[type=checkbox]', toggle );

            getFriendList('');
        }
    });
}

function search( e ) {
    var elem = $( e.currentTarget );
    getFriendList( elem.val() );
}

function save() {
    var friendsearch = '',
        checkboxes = tabSelected.find('input[type=checkbox]'),
        friends = [],
        emails = elem.find('input[name=emails]').val() || '',
        message = elem.find('[name=message]').val() || '',
        rTrim = /^\s+|\s+$/g,
        params;

    checkboxes.each(function() {
        friends.push( this.value );
    });

    emails = emails.replace( rTrim, '' );
    message = message.replace( rTrim, '' );

    params = [
        [ 'friendsearch', friendsearch ],
        [ 'emails', emails ],
        [ 'message', message ],
        [ 'friends', friends.join(',') ],
    ];

    joms.ajax({
        func: 'system,ajaxSubmitInvitation',
        data: [ 'groups,inviteUsers', id, params ],
        callback: function() {
            elem.off();
            popup.close();
        }
    });
}

function changeTab( e ) {
    var $el = $( e.target ),
        selected = $el.attr('href') === '#joms-popup-tab-selected',
        lang = window.joms_lang[ selected ? 'COM_COMMUNITY_UNSELECT_ALL' : 'COM_COMMUNITY_SELECT_ALL' ];

    btnSelect.html( lang );
}

function selectAll() {
    var ct = $('.joms-tab__content:visible'),
        clone;

    // Remove selected.
    if ( ct.attr('id') === 'joms-popup-tab-selected' ) {
        ct.find('.joms-js--friend').remove();
        elem.find('input[type=checkbox]').each(function() {
            this.checked = false;
        });
        return;
    }

    // Add selected.
    clone = ct.find('.joms-js--friend').clone();
    clone.find('input[type=checkbox]').add( ct.find('input[type=checkbox]') ).prop( 'checked', 'checked' );
    ct = elem.find('#joms-popup-tab-selected');
    ct.html( clone );
}

function load() {
    getFriendList();
}

function toggle( e ) {
    var checkbox = $( e.target ),
        ct = checkbox.closest('.joms-tab__content'),
        id, clone;

    // Remove selected.
    if ( ct.attr('id') === 'joms-popup-tab-selected' ) {
        id = checkbox[0].value;
        checkbox.closest('.joms-js--friend').remove();
        elem.find('.joms-js--friend-' + id + ' input[type=checkbox]')[0].checked = false;
        return;
    }

    // Remove selected.
    if ( !checkbox[0].checked ) {
        id = checkbox[0].value;
        elem.find('#joms-popup-tab-selected .joms-js--friend-' + id).remove();
        return;
    }

    // Add selected.
    ct = elem.find('#joms-popup-tab-selected');
    clone = checkbox.closest('.joms-js--friend').clone();
    checkbox = clone.find('input[type=checkbox]');
    checkbox[0].checked = true;
    ct.append( clone );
}

function getFriendList( _keyword ) {
    var isReset = typeof _keyword === 'string';

    if ( isReset ) {
        tabAll.empty();
        start = 0;
        keyword = _keyword;
    } else {
        start += limit;
    }

    xhr && xhr.abort();
    xhr = joms.ajax({
        func: 'system,ajaxLoadFriendsList',
        data: [ keyword, 'groups,inviteUsers', id, start, limit ],
        callback: function( json ) {
            var html;

            if ( json.html ) {
                html = $( $.trim( json.html ) );
                html.each(function() {
                    var checkbox = $( this ).find(':checkbox'),
                        value = checkbox.val();

                    if ( tabSelected.find(':checkbox[value=' + value + ']').length ) {
                        checkbox[0].checked = true;
                    }
                });

                tabAll.append( html );
            }

            if ( !isReset ) {
                tabAll[0].scrollTop = tabAll[0].scrollHeight;
            }

            // Toggle load more.
            if ( json.loadMore ) {
                btnSelect.css({ width: '49%', marginRight: '2%' });
                btnLoad.css({ width: '49%' }).html( window.joms_lang.COM_COMMUNITY_INVITE_LOAD_MORE + ' (' + json.moreCount + ')' ).show();
            } else {
                btnLoad.hide();
                btnSelect.css({ width: '100%', marginRight: '0' });
            }
        }
    });
}

function buildHtml( json ) {
    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div data-ui-object="popup-step-1"', ( json.error ? ' class="joms-popup__hide"' : '' ), '>',
            '<div class="joms-popup__content">', json.html, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--primary" data-btn-save="1">', json.btnInvite, '</button>',
            '</div>',
        '</div>',
        '<div data-ui-object="popup-step-2"', ( json.error ? '' : ' class="joms-popup__hide"' ), '>',
            '<div class="joms-popup__content joms-popup__content--single" data-ui-object="popup-message">', (json.error || ''), '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.group || (joms.popup.group = {});
    joms.popup.group.join = factory( root, $ );

    define('popups/group.join',[ 'utils/popup' ], function() {
        return joms.popup.group.join;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'groups,ajaxJoinGroup',
        data: [ id ],
        callback: function() {
            window.location.reload();
        }
    });
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.group || (joms.popup.group = {});
    joms.popup.group.leave = factory( root, $ );

    define('popups/group.leave',[ 'utils/popup' ], function() {
        return joms.popup.group.leave;
    });

})( window, joms.jQuery, function() {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'groups,ajaxShowLeaveGroup',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    elem.find('form')[0].submit();
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1">',
            '<div class="joms-popup__content">', json.html, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnNo, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-popup__hide joms-js--step2">',
            '<div class="joms-popup__content joms-popup__content--single"></div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.group || (joms.popup.group = {});
    joms.popup.group.addFeatured = factory( root, $ );

    define('popups/group.addfeatured',[ 'utils/popup' ], function() {
        return joms.popup.group.addFeatured;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'groups,ajaxAddFeatured',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.st.callbacks || (popup.st.callbacks = {});
            popup.st.callbacks.close = function() {
                window.location.reload();
            };

            popup.updateItemHTML();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.html || json.error || '' ), '</div>',
        '<div class="joms-popup__action">',
        '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.group || (joms.popup.group = {});
    joms.popup.group.removeFeatured = factory( root, $ );

    define('popups/group.removefeatured',[ 'utils/popup' ], function() {
        return joms.popup.group.removeFeatured;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'groups,ajaxRemoveFeatured',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.st.callbacks || (popup.st.callbacks = {});
            popup.st.callbacks.close = function() {
                window.location.reload();
            };

            popup.updateItemHTML();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.html || json.error || '' ), '</div>',
        '<div class="joms-popup__action">',
        '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.group || (joms.popup.group = {});
    joms.popup.group.report = factory( root );

    define('popups/group.report',[ 'utils/popup' ], function() {
        return joms.popup.group.report;
    });

})( window, function() {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'system,ajaxReport',
        data: [],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'change', 'select', changeText );
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function changeText( e ) {
    elem.find('textarea').val( e.target.value );
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    var rTrim = /^\s+|\s+$/g,
        message;

    message = elem.find('textarea').val();
    message = message.replace( rTrim, '' );

    if ( !message ) {
        elem.find('.joms-js--error').show();
        return;
    }

    elem.find('.joms-js--error').hide();

    joms.ajax({
        func: 'system,ajaxSendReport',
        data: [ 'groups,reportGroup', window.location.href, message, id ],
        callback: function( json ) {
            elem.find('.joms-js--step1').hide();
            elem.find('.joms-js--step2').show().children().html( json.error || json.message );
        }
    });
}


function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock joms-popup--500">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1', ( json.error ? ' joms-popup__hide' : '' ), '">',
            json.html,
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnCancel, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnSend, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-js--step2', ( json.error ? '' : ' joms-popup__hide' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', ( json.error || '' ), '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.group || (joms.popup.group = {});
    joms.popup.group.unpublish = factory( root );

    define('popups/group.unpublish',[ 'utils/popup' ], function() {
        return joms.popup.group.unpublish;
    });

})( window, function() {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'groups,ajaxShowUnpublishGroup',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    elem.find('form')[0].submit();
}


function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content">', json.html, '</div>',
        '<div class="joms-popup__action">',
        '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnNo, '</button> &nbsp;',
        '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnYes, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.group || (joms.popup.group = {});
    joms.popup.group.approve = factory( root, $ );

    define('popups/group.approve',[ 'functions/notification' ], function() {
        return joms.popup.group.approve;
    });

})( window, joms.jQuery, function( window, $ ) {

var id, userid;

function render( _id, _userid ) {
    id = _id;
    userid = _userid;

    joms.ajax({
        func: 'groups,ajaxApproveMember',
        data: [ userid, id ],
        callback: function( json ) {
            if ( json ) {
                $( '.joms-js--request-buttons-group-' + id + '-' + userid ).remove();
                $( '.joms-js--request-notice-group-' + id + '-' + userid ).html( json.message || json.error );
                json.success && joms.fn.notification.updateCounter( 'general', id, -1 );
            }
        }
    });
}

// Exports.
return function( id, userId ) {
    render( id, userId );
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.group || (joms.popup.group = {});
    joms.popup.group.removeMember = factory( root, $ );

    define('popups/group.removemember',[ 'utils/popup', 'functions/notification' ], function() {
        return joms.popup.group.removeMember;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, id, userid;

function render( _popup, _id, _userid ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;
    userid = _userid;

    joms.ajax({
        func: 'groups,ajaxConfirmMemberRemoval',
        data: [ userid, id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    var checkbox = elem.find('input:checkbox'),
        func = checkbox[0].checked ? 'groups,ajaxBanMember' : 'groups,ajaxRemoveMember',
        data = [ userid, id ];

    joms.ajax({
        func: func,
        data: data,
        callback: function( json ) {
            elem.find('.joms-js--step1').hide();
            elem.find('.joms-js--step2').show().children().append( json.error || json.message );

            if ( json.success ) {
                $( '.joms-js--member-group-' + id + '-' + userid ).remove();
                $( '.joms-js--request-buttons-group-' + id + '-' + userid ).remove();
                $( '.joms-js--request-notice-group-' + id + '-' + userid ).html( json && json.message || '' );
                joms.fn.notification.updateCounter( 'general', id, -1 );
            }
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1">',
            '<div class="joms-popup__content">', json.html, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnNo, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-popup__hide joms-js--step2">',
            '<div class="joms-popup__content joms-popup__content--single"></div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id, userId ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id, userId );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.group || (joms.popup.group = {});
    joms.popup.group.banMember = factory( root, $ );

    define('popups/group.banmember',[ 'utils/popup' ], function() {
        return joms.popup.group.banMember;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, id, userid;

function render( _popup, _id, _userid ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;
    userid = _userid;

    joms.ajax({
        func: 'groups,ajaxBanMember',
        data: [ userid, id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            if ( json.success ) {
                popup.st.callbacks || (popup.st.callbacks = {});
                popup.st.callbacks.close = function() {
                    window.location.reload();
                };
            }
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', ( json.title || '' ), '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.html || json.message || json.error || '' ), '</div>',
        '<div class="joms-popup__action">',
        '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id, userid ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id, userid );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.group || (joms.popup.group = {});
    joms.popup.group.unbanMember = factory( root, $ );

    define('popups/group.unbanmember',[ 'utils/popup' ], function() {
        return joms.popup.group.unbanMember;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, id, userid;

function render( _popup, _id, _userid ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;
    userid = _userid;

    joms.ajax({
        func: 'groups,ajaxUnbanMember',
        data: [ userid, id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            if ( json.success ) {
                popup.st.callbacks || (popup.st.callbacks = {});
                popup.st.callbacks.close = function() {
                    window.location.reload();
                };
            }
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', ( json.title || '' ), '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.html || json.message || json.error || '' ), '</div>',
        '<div class="joms-popup__action">',
        '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id, userid ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id, userid );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.group = factory( root, joms.popup.group || {});

    define('popups/group',[
        'popups/group.delete',
        'popups/group.invite',
        'popups/group.join',
        'popups/group.leave',
        'popups/group.addfeatured',
        'popups/group.removefeatured',
        'popups/group.report',
        'popups/group.unpublish',
        'popups/group.approve',
        'popups/group.removemember',
        'popups/group.banmember',
        'popups/group.unbanmember'
    ], function() {
        return joms.popup.group;
    });

})( window, function( window, sub ) {

// Exports.
return joms._.extend({}, sub );

});


(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.inbox || (joms.popup.inbox = {});
    joms.popup.inbox.addRecipient = factory( root, $ );

    define('popups/inbox.addrecipient',[ 'utils/popup' ], function() {
        return joms.popup.inbox.addRecipient;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, tabAll, tabSelected, btnSelect, btnLoad, id, keyword, start, limit, xhr;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    limit = 200;

    joms.ajax({
        func: 'system,ajaxShowFriendsForm',
        data: [ null, '', id, 1, 1 ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            // Override limit supplied in json.
            if ( json.limit ) {
                limit = +json.limit;
            }

            elem = popup.contentContainer;
            tabAll = elem.find('.joms-tab__content').eq(0);
            tabSelected = elem.find('.joms-tab__content').eq(1);
            btnSelect = elem.find('[data-btn-select]');
            btnLoad = elem.find('[data-btn-load]');

            elem.on( 'keyup', '[data-search]', search );
            elem.on( 'click', '.joms-tab__bar a', changeTab );
            elem.on( 'click', '[data-btn-select]', selectAll );
            elem.on( 'click', '[data-btn-load]', load );
            elem.on( 'click', '[data-btn-save]', select );
            elem.on( 'click', 'input[type=checkbox]', toggle );

            getFriendList('');
        }
    });
}

function search( e ) {
    var elem = $( e.currentTarget );
    getFriendList( elem.val() );
}

function select() {
    var to = $('#joms-js--compose-to');
    tabSelected.find('.joms-js--friend').each(function() {
        var item = $( this ),
            chk = item.find(':checkbox'),
            id = chk.val();

        if ( !to.find( '.joms-js--friend-' + id ).length ) {
            to.append( item.clone() );
        }
    });

    // Close popup.
    elem.off();
    popup.close();
    to.show();
}

function changeTab( e ) {
    var $el = $( e.target ),
        selected = $el.attr('href') === '#joms-popup-tab-selected',
        lang = window.joms_lang[ selected ? 'COM_COMMUNITY_UNSELECT_ALL' : 'COM_COMMUNITY_SELECT_ALL' ];

    btnSelect.html( lang );
}

function selectAll() {
    var ct = $('.joms-tab__content:visible'),
        clone;

    // Remove selected.
    if ( ct.attr('id') === 'joms-popup-tab-selected' ) {
        ct.find('.joms-js--friend').remove();
        elem.find('input[type=checkbox]').each(function() {
            this.checked = false;
        });
        return;
    }

    // Add selected.
    clone = ct.find('.joms-js--friend').clone();
    clone.find('input[type=checkbox]').add( ct.find('input[type=checkbox]') ).prop( 'checked', 'checked' );
    ct = elem.find('#joms-popup-tab-selected');
    ct.html( clone );
}

function load() {
    getFriendList();
}

function toggle( e ) {
    var checkbox = $( e.target ),
        ct = checkbox.closest('.joms-tab__content'),
        id, clone;

    // Remove selected.
    if ( ct.attr('id') === 'joms-popup-tab-selected' ) {
        id = checkbox[0].value;
        checkbox.closest('.joms-js--friend').remove();
        elem.find('.joms-js--friend-' + id + ' input[type=checkbox]')[0].checked = false;
        return;
    }

    // Remove selected.
    if ( !checkbox[0].checked ) {
        id = checkbox[0].value;
        elem.find('#joms-popup-tab-selected .joms-js--friend-' + id).remove();
        return;
    }

    // Add selected.
    ct = elem.find('#joms-popup-tab-selected');
    clone = checkbox.closest('.joms-js--friend').clone();
    checkbox = clone.find('input[type=checkbox]');
    checkbox[0].checked = true;
    ct.append( clone );
}

function getFriendList( _keyword ) {
    var isReset = typeof _keyword === 'string';

    if ( isReset ) {
        tabAll.empty();
        start = 0;
        keyword = _keyword;
    } else {
        start += limit;
    }

    xhr && xhr.abort();
    xhr = joms.ajax({
        func: 'system,ajaxLoadFriendsList',
        data: [ keyword, 'friends,inviteUsers', id, start, limit ],
        callback: function( json ) {
            var html;

            if ( json.html ) {
                html = $( $.trim( json.html ) );
                html.each(function() {
                    var checkbox = $( this ).find(':checkbox'),
                        value = checkbox.val();

                    if ( tabSelected.find(':checkbox[value=' + value + ']').length ) {
                        checkbox[0].checked = true;
                    }
                });

                tabAll.append( html );
            }

            if ( !isReset ) {
                tabAll[0].scrollTop = tabAll[0].scrollHeight;
            }

            // Toggle load more.
            if ( json.loadMore ) {
                btnSelect.css({ width: '49%', marginRight: '2%' });
                btnLoad.css({ width: '49%' }).html( window.joms_lang.COM_COMMUNITY_INVITE_LOAD_MORE + ' (' + json.moreCount + ')' ).show();
            } else {
                btnLoad.hide();
                btnSelect.css({ width: '100%', marginRight: '0' });
            }
        }
    });
}

function buildHtml( json ) {
    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div data-ui-object="popup-step-1"', ( json.error ? ' class="joms-popup__hide"' : '' ), '>',
            '<div class="joms-popup__content">', json.html, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--primary" data-btn-save="1">', json.btnSelect, '</button>',
            '</div>',
        '</div>',
        '<div data-ui-object="popup-step-2"', ( json.error ? '' : ' class="joms-popup__hide"' ), '>',
            '<div class="joms-popup__content joms-popup__content--single" data-ui-object="popup-message">', (json.error || ''), '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function (root, $, factory) {

    joms.popup || (joms.popup = {});
    joms.popup.chat || (joms.popup.chat = {});
    joms.popup.chat.addRecipient = factory(root, $);

    define('popups/chat.addrecipient',['utils/popup'], function () {
        return joms.popup.chat.addRecipient;
    });

})(window, joms.jQuery, function (window, $) {

    var popup, elem, tabAll, tabSelected, btnSelect, btnLoad, id, keyword, start, limit, xhr, timeout;

    function render(_popup, _id) {
        if (elem)
            elem.off();
        popup = _popup;
        id = _id;

        limit = 200;

        joms.ajax({
            func: 'system,ajaxShowFriendsForm',
            data: [null, '', '', 1, 1],
            callback: function (json) {
                popup.items[0] = {
                    type: 'inline',
                    src: buildHtml(json)
                };

                popup.updateItemHTML();

                // Override limit supplied in json.
                if (json.limit) {
                    limit = +json.limit;
                }

                elem = popup.contentContainer;
                tabAll = elem.find('.joms-tab__content').eq(0);
                tabSelected = elem.find('.joms-tab__content').eq(1);
                btnSelect = elem.find('[data-btn-select]');
                btnLoad = elem.find('[data-btn-load]');

                elem.on('input', '[data-search]', function(e) { search(e, _id)});
                elem.on('click', '.joms-tab__bar a', changeTab);
                elem.on('click', '[data-btn-select]', selectAll);
                elem.on('click', '[data-btn-load]', function(e) { load(e, _id)});
                elem.on('click', '[data-btn-save]', select);
                elem.on('click', 'input[type=checkbox]', toggle);

                getFriendList('', _id);
            }
        });
    }

    function search(e, _id) {
        var elem = $(e.currentTarget);
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            getFriendList(elem.val(), _id);
        }, 300);
    }

    function select() {
        var added_friends = {};
        tabSelected.find('.joms-js--friend').each(function () {
            var item = $(this),
                chk = item.find(':checkbox'),
                id = chk.val(),
                friend = {
                    id : +id,
                    name: item.find('.joms-avatar--comment a img').attr('alt'),
                    avatar: item.find('.joms-avatar--comment a img').attr('src')
                };
            added_friends[id] = friend;    
        });
        joms_observer.do_action('chat_add_people', added_friends);
        // Close popup.
        elem.off();
        popup.close();
    }

    function changeTab(e) {
        var $el = $(e.target),
            selected = $el.attr('href') === '#joms-popup-tab-selected',
            lang = window.joms_lang[ selected ? 'COM_COMMUNITY_UNSELECT_ALL' : 'COM_COMMUNITY_SELECT_ALL' ];

        btnSelect.html(lang);
    }

    function selectAll() {
        var ct = $('.joms-tab__content:visible'),
            clone;

        // Remove selected.
        if (ct.attr('id') === 'joms-popup-tab-selected') {
            ct.find('.joms-js--friend').remove();
            elem.find('input[type=checkbox]').each(function () {
                this.checked = false;
            });
            return;
        }

        // Add selected.
        clone = ct.find('.joms-js--friend').clone();
        clone.find('input[type=checkbox]').add(ct.find('input[type=checkbox]')).prop('checked', 'checked');
        ct = elem.find('#joms-popup-tab-selected');
        ct.html(clone);
    }

    function load(e, _id) {
        getFriendList('', _id);
    }

    function toggle(e) {
        var checkbox = $(e.target),
            ct = checkbox.closest('.joms-tab__content'),
            id, clone;

        // Remove selected.
        if (ct.attr('id') === 'joms-popup-tab-selected') {
            id = checkbox[0].value;
            checkbox.closest('.joms-js--friend').remove();
            elem.find('.joms-js--friend-' + id + ' input[type=checkbox]')[0].checked = false;
            return;
        }

        // Remove selected.
        if (!checkbox[0].checked) {
            id = checkbox[0].value;
            elem.find('#joms-popup-tab-selected .joms-js--friend-' + id).remove();
            return;
        }

        // Add selected.
        ct = elem.find('#joms-popup-tab-selected');
        clone = checkbox.closest('.joms-js--friend').clone();
        checkbox = clone.find('input[type=checkbox]');
        checkbox[0].checked = true;
        ct.append(clone);
    }

    function getFriendList(_keyword, _id) {
        var isReset = typeof _keyword === 'string';
        var users = _id && typeof _id === 'string' ? _id.split(',') : [];

        if (isReset) {
            tabAll.empty();
            start = 0;
            keyword = _keyword;
        } else {
            start += limit;
        }

        xhr && xhr.abort();
        xhr = joms.ajax({
            func: 'system,ajaxLoadFriendsList',
            data: [keyword, 'friends,inviteUsers', id, start, limit],
            callback: function (json) {
                var html;

                if (json.html) {
                    html = $($.trim(json.html));
                    html.each(function () {
                        var elm = $(this),
                            checkbox = elm.find(':checkbox'),
                            value = checkbox.val();

                        if (tabSelected.find(':checkbox[value=' + value + ']').length) {
                            checkbox[0].checked = true;
                        }


                    });

                    tabAll.append(html);

                    _.each(users, function(id) {
                        var added = tabAll.find('.joms-js--friend-' + id);
                        if (added.length) {
                            added.find('.joms-stream__time label').text(joms_lang.COM_COMMUNITY_APPS_LIST_ADDED);
                        }
                    });
                }

                if (!isReset) {
                    tabAll[0].scrollTop = tabAll[0].scrollHeight;
                }

                // Toggle load more.
                if (json.loadMore) {
                    btnSelect.css({width: '49%', marginRight: '2%'});
                    btnLoad.css({width: '49%'}).html(window.joms_lang.COM_COMMUNITY_INVITE_LOAD_MORE + ' (' + json.moreCount + ')').show();
                } else {
                    btnLoad.hide();
                    btnSelect.css({width: '100%', marginRight: '0'});
                }
            }
        });
    }

    function buildHtml(json) {
        return [
            '<div class="joms-popup joms-popup--whiteblock">',
            '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
            '<div data-ui-object="popup-step-1"', (json.error ? ' class="joms-popup__hide"' : ''), '>',
            '<div class="joms-popup__content">', json.html, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--primary" data-btn-save="1">', json.btnSelect, '</button>',
            '</div>',
            '</div>',
            '<div data-ui-object="popup-step-2"', (json.error ? '' : ' class="joms-popup__hide"'), '>',
            '<div class="joms-popup__content joms-popup__content--single" data-ui-object="popup-message">', (json.error || ''), '</div>',
            '</div>',
            '</div>'
        ].join('');
    }

// Exports.
    return function (id) {
        joms.util.popup.prepare(function (mfp) {
            render(mfp, id);
        });
    };

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.inbox || (joms.popup.inbox = {});
    joms.popup.inbox.remove = factory( root, $ );

    define('popups/inbox.remove',[ 'utils/popup' ], function() {
        return joms.popup.inbox.remove;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, task, msgids;

function render( _popup, _task, _msgids ) {
    var data;

    if ( elem ) elem.off();
    popup = _popup;
    task = _task;
    msgids = _msgids;

    data = [ task ];
    msgids.length || data.push('empty');

    joms.ajax({
        func: 'inbox,ajaxDeleteMessages',
        data: data,
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    joms.ajax({
        func: task === 'inbox' ? 'inbox,ajaxRemoveFullMessages' : 'inbox,ajaxRemoveSentMessages',
        data: [ msgids.join(',') ],
        callback: function( json ) {
            var i;

            elem.find('.joms-js--step1').hide();
            elem.find('.joms-js--step2').show().children().html( json.error || json.message );

            if ( json.success ) {
                $('.joms-js--message-checkall')[0].checked = false;
                for ( i = 0; i < msgids.length; i++ ) {
                    $( '.joms-js--message-item-' + msgids[i] ).remove();
                }
                if ( !$('.joms-js--message-item').length ) {
                    $('.joms-js--message-ct').remove();
                }
            }
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1 ', ( json.error ? 'joms-popup__hide' : '' ), '">',
            '<div class="joms-popup__content">', ( json.html || '' ), '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnNo, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-js--step2 ', ( json.error ? '' : 'joms-popup__hide' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', ( json.error || '' ), '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( task, msgids ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, task, msgids );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.inbox || (joms.popup.inbox = {});
    joms.popup.inbox.setRead = factory( root, $ );

    define('popups/inbox.setread',[ 'utils/popup' ], function() {
        return joms.popup.inbox.setRead;
    });

})( window, joms.jQuery, function( window ) {

function render( msgids, error ) {
    var i;

    if ( !msgids.length ) {
        joms.util.popup.prepare(function( mfp ) {
            mfp.items[0] = { type: 'inline', src: buildHtml({ error: error }) };
            mfp.updateItemHTML();
        });
        return;
    }

    for ( i = 0; i < msgids.length; i++ ) {
        window.jax.call( 'community', 'inbox,ajaxMarkMessageAsRead', msgids[ i ] );
    }
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', ( json.title || '' ), '</div>',
        '<div class="joms-js--step1 ', ( json.error ? 'joms-popup__hide' : '' ), '">',
            '<div class="joms-popup__content">', ( json.html || '' ), '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnNo, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-js--step2 ', ( json.error ? '' : 'joms-popup__hide' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', ( json.error || '' ), '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( msgids, error ) {
    render( msgids, error );
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.inbox || (joms.popup.inbox = {});
    joms.popup.inbox.setUnread = factory( root, $ );

    define('popups/inbox.setunread',[ 'utils/popup' ], function() {
        return joms.popup.inbox.setUnread;
    });

})( window, joms.jQuery, function( window ) {

function render( msgids, error ) {
    var i;

    if ( !msgids.length ) {
        joms.util.popup.prepare(function( mfp ) {
            mfp.items[0] = { type: 'inline', src: buildHtml({ error: error }) };
            mfp.updateItemHTML();
        });
        return;
    }

    for ( i = 0; i < msgids.length; i++ ) {
        window.jax.call( 'community', 'inbox,ajaxMarkMessageAsUnread', msgids[ i ] );
    }
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', ( json.title || '' ), '</div>',
        '<div class="joms-js--step1 ', ( json.error ? 'joms-popup__hide' : '' ), '">',
            '<div class="joms-popup__content">', ( json.html || '' ), '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnNo, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-js--step2 ', ( json.error ? '' : 'joms-popup__hide' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', ( json.error || '' ), '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( msgids, error ) {
    render( msgids, error );
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.inbox = factory( root, joms.popup.inbox || {});

    define('popups/inbox',[
        'popups/inbox.addrecipient',
        'popups/chat.addrecipient',
        'popups/inbox.remove',
        'popups/inbox.setread',
        'popups/inbox.setunread'
    ], function() {
        return joms.popup.inbox;
    });

})( window, function( window, sub ) {

// Exports.
return joms._.extend({}, sub );

});


(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.location || (joms.popup.location = {});
    joms.popup.location.view = factory( root );

    define('popups/location.view',[ 'utils/popup' ], function() {
        return joms.popup.location.view;
    });

})( window, function( root ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'activities,ajaxShowMap',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
        }
    });
}

function buildHtml( json ) {
    var latlng, location, src;

    json || (json = {});

    latlng = json.latitude + ',' + json.longitude;
    location = json.location;
    src = 'https://maps.googleapis.com/maps/api/staticmap?center=' + latlng +
        '&markers=color:red%7Clabel:S%7C' + latlng + '&zoom=14&size=600x350&maptype=roadmap';

    if ( root.joms_gmap_key ) {
        src += '&key=' + root.joms_gmap_key;
    }

    return [
        '<div class="joms-popup joms-popup--location-view">',
        '<div', ( json.error ? ' class="joms-popup__hide"' : '' ), '>',
            '<a href="//www.google.com/maps/@', latlng, ',19z" target="_blank">',
            '<img src="', src, '">',
            '</a>',
        '</div>',
        '<div', ( json.error ? '' : ' class="joms-popup__hide"' ), '>',
            '<div class="joms-popup__content joms-popup__content--single">', json.error, '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.location = factory( root, joms.popup.location || {});

    define('popups/location',[ 'popups/location.view' ], function() {
        return joms.popup.location;
    });

})( window, function( window, sub ) {

// Exports.
return joms._.extend({}, sub );

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.notification || (joms.popup.notification = {});
    joms.popup.notification.global = factory( root );

    define('popups/notification.global',[ 'utils/popup' ], function() {
        return joms.popup.notification.global;
    });

})( window, function() {

function render( popup ) {
    joms.ajax({
        func: 'notification,ajaxGetNotification',
        data: [ '' ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title">', ( json.title || '' ), '</div>',
        '<div class="joms-popup__content joms-popup__content--single" style="max-height:400px;overflow:auto">',
        '<ul style="margin: 0; list-style: none;">', ( json.html || '' ), '</ul>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function() {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp);
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.notification || (joms.popup.notification = {});
    joms.popup.notification.friend = factory( root );

    define('popups/notification.friend',[ 'utils/popup' ], function() {
        return joms.popup.notification.friend;
    });

})( window, function() {

function render( popup ) {
    joms.ajax({
        func: 'notification,ajaxGetRequest',
        data: [ '' ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title">', ( json.title || '' ), '</div>',
        '<div class="joms-popup__content joms-popup__content--single" style="max-height:400px;overflow:auto">',
        '<ul style="margin: 0; list-style: none;">', ( json.html || '' ), '</ul>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function() {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp);
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.notification || (joms.popup.notification = {});
    joms.popup.notification.chat = factory( root, $ );

    define('popups/notification.chat',[ 'utils/popup' ], function() {
        return joms.popup.notification.chat;
    });

})( window, joms.jQuery, function( window, $ ) {

function render( popup, elem ) {
    var $popover = $( elem ).next( '.joms-popover--toolbar-chat' ),
        json = {};

    json.title = window.joms_lang && joms_lang.COM_COMMUNITY_MESSAGE || 'Message';
    json.html = $popover.html();

    popup.items[0] = {
        type: 'inline',
        src: buildHtml( json )
    };

    popup.updateItemHTML();
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title">', ( json.title || '' ), '</div>',
        '<div class="joms-popup__content joms-popup__content--single" style="max-height:400px;overflow:auto">',
        '<ul style="margin: 0; list-style: none;">', ( json.html || '' ), '</ul>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( elem ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, elem );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.notification = factory( root, joms.popup.notification || {});

    define('popups/notification',[
        'popups/notification.global',
        'popups/notification.friend',
        'popups/notification.chat'
    ], function() {
        return joms.popup.notification;
    });

})( window, function( window, sub ) {

// Exports.
return joms._.extend({}, sub );

});

(function( root, $, factory ) {

    joms.util || (joms.util = {});
    joms.util.phototag = factory( root, $ );

})( window, joms.jQuery, function( window, $, undef ) {

var wrapper, elem, img, friends, callback, tagAdded, isPopup, noResult;

function populate( el, tags ) {
    var cssTags = '.joms-phototag__tags',
        cssTag = '.joms-phototag__tag',
        $tags, $tag, tag, pos, top, left, width, tagTop, tagLeft, tagWidth, tagHeight, height, i;

    $( cssTags ).remove();

    if ( tags && tags.length ) {
        // Image measurements.
        img    = $( el );
        pos    = img.position();
        top    = pos.top;
        left   = pos.left;
        width  = img.width();
        height = img.height();

        // Tags container.
        $tags = $( '<div class=' + cssTags.substr(1) + '></div>' );
        $tags.css({
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            margin: 'auto',
            width: width,
            height: height
        });

        $tags.insertAfter( img );

        for ( i = 0; i < tags.length; i++ ) {
            tag       = tags[ i ];
            tagTop    = Math.round( height * tag.top );
            tagLeft   = Math.round( width * tag.left );
            tagWidth  = Math.round( width * tag.width );
            tagHeight = Math.round( height * tag.height );

            // Force square.
            tagHeight = tagWidth = Math.max( 10, Math.min( tagWidth, tagHeight ) );

            $tag = $( '<div class=' + cssTag.substr(1) + '><span>' + tag.displayName + '</span></div>' );
            $tag.css({
                top    : tagTop + 'px',
                left   : tagLeft + 'px',
                width  : tagWidth + 'px',
                height : tagHeight + 'px'
            });
            $tag.appendTo( $tags );
        }
    }
}

function create( e, tags, type, groupid, eventid ) {
    var imgOffset, parOffset, pos, top, left, width, height;

    destroy();

    // Hide tagging info.
    $( '.joms-phototag__tags' ).hide();

    wrapper   = $( buildHtml() );
    elem      = wrapper.find('.joms-phototag');
    img       = $( e.target );
    friends   = undef;
    callback  = {};
    width     = img.width();
    height    = img.height();
    imgOffset = img.offset();
    parOffset = img.parent().offset();
    top       = imgOffset.top - parOffset.top;
    left      = imgOffset.left - parOffset.left;
    tagAdded  = tags || [];
    isPopup   = type !== 'page' ? true : false;

    elem.css({
        top: 0,
        left: 0
    });

    wrapper.css({
        top: top,
        left: left,
        width: width,
        height: height
    });

    wrapper.insertAfter( img );

    pos = calcClickPosition( e );

    elem.css({ top: pos.top, left: pos.left });
    elem.on( 'keyup', 'input', filter );
    elem.on( 'click', 'a[data-id]', select );
    elem.on( 'click', 'button', destroy );
    elem.on( 'click', function( e ) {
        e.stopPropagation();
    });
    wrapper.on( 'click', moveBoxPosition );

    if ( +groupid ) {
        joms.fn.tagging.fetchGroupMembers( groupid, function( members ) {
            friends = members;
            filter();
        });
    } else if ( +eventid ) {
        joms.fn.tagging.fetchEventMembers( eventid, function( members ) {
            friends = members;
            filter();
        });
    } else {
        filter();
    }

    // Apparently Android (Chrome?) trigger "onresize" event when keypad being shown,
    // which make phototag immediately closed. Add resize handler only on desktop browser.
    if ( !joms.mobile ) {
        $( window ).on( 'resize.phototag', destroy );
    }
}

function filter( e ) {
    var input, keyword, filtered, ac;

    if ( !friends ) {
        friends = window.joms_friends || [];
    }

    input = $( e ? e.currentTarget : elem.find('input') );
    keyword = input.val().replace( /^\s+|\s+$/g, '' ).toLowerCase();
    filtered = friends;

    filtered = joms._.filter( friends, function( obj ) {
        if ( !obj ) return false;
        if ( !obj.name ) return false;
        if ( tagAdded && tagAdded.indexOf( obj.id + '' ) >= 0 ) return false;
        if ( keyword && obj.name.toLowerCase().indexOf( keyword ) < 0 ) return false;
        return true;
    });

    filtered = filtered.slice(0, 8);
    filtered = joms._.map( filtered, function( obj ) {
        return '<a href="javascript:" data-id="' + obj.id + '">' + obj.name + '</a>';
    });

    if ( !filtered.length ) {
        filtered = [ '<span><em>' + window.joms_lang.COM_COMMUNITY_NO_RESULT_FOUND + '</em></span>' ];
        noResult = true;
    } else {
        noResult = false;
    }

    ac = elem.find('.joms-phototag__autocomplete');
    ac.html( filtered.join('') );

    ac.append(
        '<div><button class="joms-button--neutral joms-button--small joms-button--full">' +
        window.joms_lang.COM_COMMUNITY_PHOTO_DONE_TAGGING +
        '</button></div>'
    );

    ac.show();
}

function select( e ) {
    var ac = elem.find('.joms-phototag__autocomplete'),
        el = $( e.currentTarget ),
        id = el.data('id') || '',
        pos;

    e.stopPropagation();
    ac.hide();

    if ( callback && callback.tagAdded ) {
        tagAdded || (tagAdded = []);
        tagAdded.push( id + '' );
        pos = calcBoxPosition();

        callback.tagAdded(
            id,
            pos.left,
            pos.top,
            pos.width,
            pos.height
        );

        filter();
    }
}

function destroy() {
    // Show tagging info.
    $( '.joms-phototag__tags' ).show();

    if ( elem ) {
        elem.remove();
        wrapper.remove();
        $( window ).off('resize.phototag');
        elem = undef;
        img = undef;

        if ( callback && callback.destroy ) {
            callback.destroy();
        }

        callback = undef;
    }
}

function on( eventType, fn ) {
    callback[ eventType ] = fn;
}

function off( eventType ) {
    if ( !eventType ) {
        callback = {};
    } else if ( callback[ eventType ] ) {
        callback[ eventType ] = undef;
    }
}

function calcClickPosition( e ) {
    var height = img.height(),
        width  = img.width(),
        offset, left, top;

    // Calculate offset position.
    if ( isPopup ) {
        top  = e.clientY - 45 - e.target.offsetTop - 43;
        left = e.clientX - 45 - e.target.offsetLeft - 43;
    } else {
        offset = img.offset();
        top    = e.pageY - offset.top - 43;
        left   = e.pageX - offset.left - 43;
    }

    // Respect wrapper boundaries.
    top  = Math.max( 0, Math.min( top, height - 86 ) );
    left = Math.max( 0, Math.min( left, width - 86 ) );

    return {
        top: top,
        left: left
    };
}

function calcBoxPosition() {
    var pos, ctWidth, ctHeight, boxWidth, boxHeight, boxLeft, boxTop;

    ctWidth = wrapper.width();
    ctHeight = wrapper.height();

    pos = elem.position();
    boxWidth = elem.width();
    boxHeight = elem.height();
    boxLeft = pos.left;
    boxTop = pos.top;

    // Percentage (relative to wrapper height).
    boxWidth = boxWidth / ctWidth;
    boxHeight = boxHeight / ctHeight;

    // Percentage (relative to wrapper dimension).
    boxLeft = boxLeft / ctWidth;
    boxTop = boxTop / ctHeight;

    return {
        top    : boxTop,
        left   : boxLeft,
        width  : boxWidth,
        height : boxHeight
    };
}

function moveBoxPosition( e ) {
    var pos;

    if ( noResult ) {
        destroy();
        return;
    }

    pos = calcClickPosition( e );

    elem.css({
        top: pos.top,
        left: pos.left
    });
}

function buildHtml() {
    return [
        '<div class=joms-phototag__wrapper>',
        '<div class=joms-phototag>',
        '<div class=joms-phototag__input>',
        '<input type=text placeholder="', window.joms_lang.COM_COMMUNITY_SEARCH,'">',
        '<div class="joms-phototag__autocomplete"></div>',
        '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return {
    populate: populate,
    create: create,
    destroy: destroy,
    on: on,
    off: off
};

});

define("utils/phototag", function(){});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.photo || (joms.popup.photo = {});
    joms.popup.photo.open = factory( root, $ );

    define('popups/photo.open',[ 'utils/popup', 'utils/phototag' ], function() {
        return joms.popup.photo.open;
    });

})( window, joms.jQuery, function( window, $ ) {

var iconCog       = '<svg viewBox="0 0 16 16" class="joms-icon"><use xlink:href="#joms-icon-cog"></use></svg>',
    iconBubble    = '<svg viewBox="0 0 16 16" class="joms-icon"><use xlink:href="#joms-icon-bubble"></use></svg>',
    iconThumbsUp  = '<svg viewBox="0 0 16 16" class="joms-icon"><use xlink:href="#joms-icon-thumbs-up"></use></svg>',
    iconTag       = '<svg viewBox="0 0 16 16" class="joms-icon"><use xlink:href="#joms-icon-tag"></use></svg>',
    iconNewspaper = '<svg viewBox="0 0 16 16" class="joms-icon"><use xlink:href="#joms-icon-newspaper"></use></svg>',

    popup, elem, img, spinner, caption, tagBtn, tags, tagLabel, tagRemoveLabel, album, id, list, index, lang,
    canEdit, canDelete, canTag, canMovePhoto, canRotate, albumName, albumUrl, photoUrl, userId, groupId, eventId, isRegistered, isOwner, isAdmin, enableDownload, enableReporting, enableSharing, enableLike;

function render( _popup, _album, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    album = _album;
    id = _id;

    joms.ajax({
        func: 'photos,ajaxGetPhotosByAlbum',
        data: [ album, id ],
        callback: function( json ) {
            json || (json = {});
            lang = json.lang || {};
            canEdit = json.can_edit || false;
            canDelete = json.can_delete || false;
            canTag = json.can_tag || false;
            canMovePhoto = json.can_move_photo || false;
            canRotate = json.can_rotate || false;
            albumName = json.album_name || 'Untitled';
            albumUrl = json.album_url;
            photoUrl = json.photo_url;

            // Priviliges.
            isRegistered = userId = +json.my_id;
            groupId = +json.groupid;
            eventId = +json.eventid;
            isOwner = isRegistered && ( +json.my_id === +json.owner_id );
            isAdmin = +json.is_admin;

            // Settings.
            enableDownload = +json.deleteoriginalphotos ? false : true;
            enableReporting = +json.enablereporting;
            enableSharing = +json.enablesharing;
            enableLike = +json.enablelike;

            if ( albumUrl ) {
                albumName = '<a href="' + albumUrl + '">' + albumName + '</a>';
            }

            popup.items[0] = {
                type: 'inline',
                src: json.error ? buildErrorHtml( json ) : buildHtml( json )
            };

            popup.updateItemHTML();

            // Override popup#close function.
            popup.close = closeOverride;

            elem = popup.contentContainer;

            // Break on error.
            if ( json.error ) {
                return;
            }

            img = elem.find('img');
            spinner = elem.find('.joms-spinner');
            caption = elem.find('.joms-popup__optcaption');
            tagBtn = elem.find('.joms-popup__btn-tag-photo');

            elem.on( 'click', '.mfp-arrow-left', prev );
            elem.on( 'click', '.mfp-arrow-right', next );
            elem.on( 'click', '.joms-popup__btn-tag-photo', tagPrepare );
            elem.on( 'click', '.joms-popup__btn-comments', toggleComments );
            elem.on( 'click', '.joms-popup__btn-comments .joms-icon', toggleComments );
            elem.on( 'click', '.joms-popup__btn-option', toggleDropdown );
            elem.on( 'click', '.joms-popup__btn-share', share );
            elem.on( 'click', '.joms-popup__btn-download', download );
            elem.on( 'click', '.joms-popup__btn-report', report );
            elem.on( 'click', '.joms-popup__btn-upload', upload );
            elem.on( 'click', '.joms-popup__btn-cover', setAsCover );
            elem.on( 'click', '.joms-popup__btn-profile', setAsProfilePicture );
            elem.on( 'click', '.joms-popup__btn-delete', _delete );
            elem.on( 'click', '.joms-popup__btn-move', moveToAnotherAlbum );
            elem.on( 'click', '.joms-popup__btn-like', like );
            elem.on( 'click', '.joms-popup__btn-dislike', dislike );
            elem.on( 'click', '.joms-popup__btn-rotate-left', rotateLeft );
            elem.on( 'click', '.joms-popup__btn-rotate-right', rotateRight );
            elem.on( 'mouseleave', '.joms-popup__dropdown--wrapper', hideDropdown );
            elem.on( 'click', '.joms-js--remove-tag', tagRemove );
            elem.on( 'click', '.joms-js--btn-desc-edit', editDescription );
            elem.on( 'click', '.joms-js--btn-desc-cancel', cancelDescription );
            elem.on( 'click', '.joms-js--btn-desc-save', saveDescription );

            // Hook arrow keys.
            $( document ).off('keyup.photomodal').on( 'keyup.photomodal', function( e ) {
                var tagName = e.target.tagName.toLowerCase(),
                    key = e.keyCode;

                // Prevent navigation when user typing.
                if ( tagName === 'input' || tagName === 'textarea' ) {
                    return;
                }

                if ( key === 37 || key === 39 ) {
                    if ( key === 37 && index > 0 ) {
                        prev();
                    } else if ( key === 39 && index < list.length - 1 ) {
                        next();
                    }
                }
            });

            // Unhook arrow keys on close.
            popup.st.callbacks || (popup.st.callbacks = {});
            popup.st.callbacks.close = function() {
                $( document ).off('keyup.photomodal');
            };

            // In case no ID is profided.
            if ( !id ) {
                id = json.list[ json.index ].id;
            }

            fetchComments( id );
            toggleArrows();
            preloadNeighbourImages();
        }
    });
}

// Image load timer.
var loadImageTimer;
var loadSpinnerTimer;

// Image loader.
function loadImage( img, url ) {
    clearTimeout( loadImageTimer );
    clearTimeout( loadSpinnerTimer );

    img.hide();
    img.removeAttr('src');

    loadSpinnerTimer = setTimeout(function() {
        spinner.show();
    }, 100 );

    loadImageTimer = setTimeout(function() {
        $('<img>').load(function() {
            clearTimeout( loadSpinnerTimer );
            spinner.hide();
            img.attr( 'src', url );
            img.show();
        }).attr( 'src', url );
    }, 1 );
}

function prev() {
    index--;
    (index < 0) && (index = list.length - 1);
    id = list[index].id;
    loadImage( img, list[index].url );
    caption.html( albumName + ' <span class="joms-popup__optcapindex">' + ( index + 1 ) + ' ' + window.joms_lang.COM_COMMUNITY_OF + ' ' + list.length + '</span>' );
    tagCancel();
    fetchComments( id );
    toggleArrows();
    preloadNeighbourImages();
}

function next() {
    index++;
    (index >= list.length) && (index = 0);
    id = list[index].id;
    loadImage( img, list[index].url );
    caption.html( albumName + ' <span class="joms-popup__optcapindex">' + ( index + 1 ) + ' ' + window.joms_lang.COM_COMMUNITY_OF + ' ' + list.length + '</span>' );
    tagCancel();
    fetchComments( id );
    toggleArrows();
    preloadNeighbourImages();
}

function toggleArrows() {
    var noprev = index <= 0,
        nonext = index >= list.length - 1;

    elem.find('.mfp-arrow-left')[ noprev ? 'hide' : 'show' ]();
    elem.find('.mfp-arrow-right')[ nonext ? 'hide' : 'show' ]();
}

function preloadNeighbourImages() {
    var noprev = index <= 0,
        nonext = index >= list.length - 1,
        img;

    if ( !noprev ) {
        img = new Image();
        img.src = list[ index - 1 ].url;
    }

    if ( !nonext ) {
        img = new Image();
        img.src = list[ index + 1 ].url;
    }
}

function tagPrepare() {
    if ( tagBtn.data('tagging') ) {
        tagCancel();
        return;
    }

    tagBtn.data( 'tagging', 1 );

    elem.find( '.joms-phototag__tags' ).hide();
    elem.children('.joms-popup--photo').addClass('joms-popup--phototag');
    img.off('click.phototag').on( 'click.phototag', tagStart );
    img.addClass('joms-phototag__image');
    elem.find('.joms-popup__btn-tag-photo').html( iconTag + ' ' + lang.done_tagging );
}

function tagStart( e ) {
    var indices = joms._.map( tags, function( item ) {
        return item.userId + '';
    });

    joms.util.phototag.create( e, indices, false, groupId, eventId );
    joms.util.phototag.on( 'tagAdded', tagAdded );
    joms.util.phototag.on( 'destroy', function() {
        tagBtn.removeData('tagging');
        elem.children('.joms-popup--photo').removeClass('joms-popup--phototag');
        img.off('click.phototag');
        img.removeClass('joms-phototag__image');
        elem.find('.joms-popup__btn-tag-photo').html( iconTag + ' <span class="joms-popup__btn-overlay">' + lang.tag_photo + '</span>' );
    });

    elem.children('.joms-popup--photo').removeClass('joms-popup--phototag');
    img.off('click.phototag');
}

function tagAdded( userId, y, x, w, h ) {
    joms.ajax({
        func: 'photos,ajaxAddPhotoTag',
        data: [ id, userId, x, y, w, h ],
        callback: function( json ) {
            var $comments, $tags;

            if ( json.error ) {
                window.alert( stripTags( json.error ) );
                return;
            }

            if ( json.success ) {
                tags.push( json.data );

                // Render tag info.
                $comments = elem.find('.joms-popup__comment');
                $tags = $comments.find('.joms-js--tag-info');
                $tags.html( _tagBuildHtml() );
            }
        }
    });
}

function tagCancel() {
    joms.util.phototag.destroy();
}

function tagRemove( e ) {
    var el = $( e.currentTarget ),
        userId = el.data('id');

    joms.ajax({
        func: 'photos,ajaxRemovePhotoTag',
        data: [ id, userId ],
        callback: function( json ) {
            var $comments, $tags, i;

            if ( json.error ) {
                window.alert( stripTags( json.error ) );
                return;
            }

            if ( json.success ) {
                for ( i = 0; i < tags.length; i++ ) {
                    if ( +userId === +tags[i].userId ) {
                        tags.splice( i--, 1 );
                    }
                }

                // Render tag info.
                $comments = elem.find('.joms-popup__comment');
                $tags = $comments.find('.joms-js--tag-info');
                $tags.html( _tagBuildHtml() );
            }

        }
    });
}

function _tagBuildHtml() {
    var html, item, str, i;

    if ( !tags || !tags.length ) {
        tags = [];
    }

    joms.util.phototag.populate( img, tags, 'page' );

    if ( !tags.length ) {
        return '';
    }

    html = [];

    for ( i = 0; i < tags.length; i++ ) {
        item = tags[i];
        str = '<a href="' + item.profileUrl + '">' + item.displayName + '</a>';

        if ( item.canRemove ) {
            str += ' (<a href="javascript:" class="joms-js--remove-tag" data-id="' + item.userId + '">' + tagRemoveLabel + '</a>)';
        }

        html.push( str );
    }

    html = html.join(', ');
    html = tagLabel + '<br>' + html;

    return html;
}

function toggleComments( e ) {
    e.stopPropagation();
    elem.children('.joms-popup').toggleClass('joms-popup--togglecomment');
}

function closeOverride() {
    var $ct = elem.children('.joms-popup'),
        className = 'joms-popup--togglecomment';

    if ( $ct.hasClass( className ) ) {
        $ct.removeClass( className );
        return;
    }

    $.magnificPopup.proto.close.call( this );
}

function toggleDropdown( e ) {
    var wrapper = $( e.target ).closest('.joms-popup__dropdown--wrapper'),
        dropdown = wrapper.children('.joms-popup__dropdown');

    dropdown.toggleClass('joms-popup__dropdown--open');
}

function hideDropdown( e ) {
    var wrapper = $( e.target ).closest('.joms-popup__dropdown--wrapper'),
        dropdown = wrapper.children('.joms-popup__dropdown');

    dropdown.removeClass('joms-popup__dropdown--open');
}

function updateDropdownHtml( json ) {
    var html = '',
        like = '',
        count, isPhotoOwner;

    json || (json = {});
    isPhotoOwner = json.is_photo_owner;

    // Dropdown.
    if ( enableSharing ) {
        html += '<a href="javascript:" class="joms-popup__btn-share">' + lang.share + '</a>';
    }

    if ( enableDownload ) {
        html += '<a href="javascript:" class="joms-popup__btn-download">' + lang.download + '</a>';
    }

    if ( isOwner || isAdmin || isPhotoOwner ) {
        html += ( enableSharing || enableDownload ? '<div class="sep"></div>' : '' );
        html += ( isOwner ? '<a href="javascript:" class="joms-popup__btn-upload">' + lang.upload_photos + '</a>' : '' );
        html += ( isOwner ? '<div class="sep"></div>' : '' );
        html += ( isOwner ? '<a href="javascript:" class="joms-popup__btn-profile">' + lang.set_as_profile_picture + '</a>' : '' );
        html += ( isOwner || isAdmin ? '<a href="javascript:" class="joms-popup__btn-cover">' + lang.set_as_album_cover + '</a>' : '' );
        html += ( canDelete ? '<a href="javascript:" class="joms-popup__btn-delete">' + lang.delete_photo + '</a>' : '' );
        html += ( canMovePhoto || isPhotoOwner ? '<a href="javascript:" class="joms-popup__btn-move">' + lang.move_to_another_album + '</a>' : '' );
        html += ( canRotate ? '<div class="sep joms-popup__btn-rotate-toggle"></div>' : '' );
        html += ( canRotate ? '<a href="javascript:" class="joms-popup__btn-rotate-left joms-popup__btn-rotate-toggle">' + lang.rotate_left + '</a>' : '' );
        html += ( canRotate ? '<a href="javascript:" class="joms-popup__btn-rotate-right joms-popup__btn-rotate-toggle">' + lang.rotate_right + '</a>' : '' );
    } else {
        html += ( canDelete ? '<a href="javascript:" class="joms-popup__btn-delete">' + lang.delete_photo + '</a>' : '' );
        html += ( enableReporting ? '<a href="javascript:" class="joms-popup__btn-report">' + lang.report + '</a>' : '' );
    }

    html = '<div class="joms-popup__dropdown"><div class="joms-popup__ddcontent">' + html + '</div></div>';

    // Like.
    if ( enableLike && json && json.like ) {
        like += '<button class="joms-popup__btn-like joms-js--like-photo-' + id + ( json.like.is_liked ? ' liked' : '' ) + '"';
        like += ' onclick="joms.api.page' + ( json.like.is_liked ? 'Unlike' : 'Like' ) + '(\'photo\', \'' + id + '\');"';
        like += ' data-lang="' + ( json.like.lang || 'Like' ) + '"';
        like += ' data-lang-like="' + ( json.like.lang_like || 'Like' ) + '"';
        like += ' data-lang-liked="' + ( json.like.lang_liked || 'Liked' ) + '">';
        like += iconThumbsUp + ' ';
        like += '<span>';
        like += ( json.like.is_liked ? json.like.lang_liked : json.like.lang_like );

        count = +json.like.count;
        if ( count > 0 ) {
            like += ' (' + count + ')';
        }

        like += '</span></button>';
    }

    elem.find('.joms-popup__dropdown').replaceWith( html );
    elem.find('.joms-popup__btn-like').replaceWith( like );
}

function toggleRotate( rotatable ) {
    var $elems = elem.find('.joms-popup__btn-rotate-toggle');
    if ( typeof rotatable === 'boolean' ) {
        rotatable ? $elems.show() : $elems.hide();
    }
}

function share() {
    joms.api.pageShare( photoUrl.replace( '___photo_id___', id ) );
}

function download() {
    window.open( list[index].original );
}

function report() {
    joms.api.photoReport( userId, photoUrl.replace( '___photo_id___', id ) );
}

function upload() {
    joms.api.photoUpload( album );
}

function setAsCover() {
    joms.ajax({
        func: 'photos,ajaxConfirmDefaultPhoto',
        data: [ album, id ],
        callback: function( json ) {
            if ( json.error ) {
                window.alert( stripTags( json.error ) );
                return;
            }

            if ( window.confirm( stripTags( json.message ) ) ) {
                setAsCoverConfirm();
            }
        }
    });
}

function setAsCoverConfirm() {
    joms.ajax({
        func: 'photos,ajaxSetDefaultPhoto',
        data: [ album, id ],
        callback: function( json ) {
            window.alert( stripTags( json.error || json.message ) );
        }
    });
}

function setAsProfilePicture() {
    joms.ajax({
        func: 'photos,ajaxLinkToProfile',
        data: [ id ],
        callback: function( json ) {
            var form, prop;

            if ( json.error ) {
                window.alert( stripTags( json.error ) );
                return;
            }

            if ( window.confirm( stripTags( json.message ) ) ) {
                json.formParams || (json.formParams = {});
                form = $('<form method=post action="' + json.formUrl + '" style="width:1px; height:1px; position:absolute"/>');
                for ( prop in json.formParams ) {
                    form.append('<input type=hidden name="' + prop + '" value="' + json.formParams[prop] + '"/>');
                }

                form.appendTo( document.body );
                form[0].submit();
            }
        }
    });
}

function _delete() {
    joms.ajax({
        func: 'photos,ajaxConfirmRemovePhoto',
        data: [ id ],
        callback: function( json ) {
            if ( json.error ) {
                window.alert( stripTags( json.error ) );
                return;
            }

            if ( window.confirm( stripTags( json.message ) ) ) {
                _deleteConfirm();
            }
        }
    });
}

function _deleteConfirm() {
    joms.ajax({
        func: 'photos,ajaxRemovePhoto',
        data: [ id ],
        callback: function( json ) {
            if ( json.error ) {
                window.alert( stripTags( json.error ) );
                return;
            }

            elem.off();
            popup.close();
            window.location.reload();
        }
    });
}

function moveToAnotherAlbum() {
    joms.api.photoSetAlbum( id );
}

function like() {

}

function dislike() {

}

function rotateLeft() {
    rotate('left');
}

function rotateRight() {
    rotate('right');
}

function rotate( direction ) {
    var id = list[index] && list[index].id;
    if ( !id ) return;

    joms.ajax({
        func: 'photos,ajaxRotatePhoto',
        data: [ id, direction ],
        callback: function( json ) {
            joms._.extend(list[index], json || {});
            img.attr( 'src', list[index].url );
        }
    });
}

function stripTags( html ) {
    html = html.replace( /<\/?[^>]+>/g, '' );
    return html;
}

function buildHtml( json ) {
    var sliderHtml, commentHtml,
        caption = '';

    json || (json = {});
    sliderHtml  = json.error || '';
    commentHtml = json.error ? '' : (json.commentHtml || '');

    if ( !json.error ) {
        list       = json.list || [];
        index      = json.index || 0;
        index      = Math.min( list.length, index );
        sliderHtml = '<img src="' + list[index].url + '" data-index="' + index + '"><div class="joms-spinner" style="display:none"></div>';
        caption    = albumName + ' <span class="joms-popup__optcapindex">' + ( index + 1 ) + ' ' + window.joms_lang.COM_COMMUNITY_OF + ' ' + list.length + '</span>';
    }

    return [
        '<div class="joms-popup joms-popup--photo">',
        '<div class="joms-popup__commentwrapper">',
        '<div class="joms-popup__content">',
        (list && ( list.length > 1 ) ? '<button class="mfp-arrow mfp-arrow-left" type="button" title="' + lang.prev + '"></button>' : ''),
        (list && ( list.length > 1 ) ? '<button class="mfp-arrow mfp-arrow-right" type="button" title="' + lang.next + '"></button>' : ''),
        sliderHtml,
        '<div class="joms-popup__option clearfix">',
        '<div class="joms-popup__optcaption">', ( caption || 'Untitled' ), '</div>',
        '<div class="joms-popup__optoption">',
        '<button class="joms-popup__btn-viewalbum" onclick="window.location=\'', albumUrl, '\'">', iconNewspaper, ' <span class="joms-popup__btn-overlay">', lang.view_album, '</span></button>',
        '<button class="joms-popup__btn-comments">', iconBubble, ' <span class="joms-popup__btn-overlay">', lang.comments, '</span></button>',
        '<button class="joms-popup__btn-like"></button>',
        ( canTag ? '<button class="joms-popup__btn-tag-photo">' + iconTag + ' <span class="joms-popup__btn-overlay">' + lang.tag_photo + '</span></button>' : '' ),
        '<div class="joms-popup__dropdown--wrapper"><div class="joms-popup__dropdown"></div><button class="joms-popup__btn-option">', iconCog, ' <span class="joms-popup__btn-overlay">', lang.options, '</span></button></div>',
        '</div>',
        '</div>',
        '</div>',
        '<div class="joms-popup__comment">', commentHtml, '</div>',
        '<button class="mfp-close" type="button" title="Close (Esc)">×</button>',
        '</div>',
        '</div>'
    ].join('');
}

function buildErrorHtml( json ) {
    json || (json = {});
    json.title || (json.title = '&nbsp;');

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', json.error, '</div>',
        '</div>'
    ].join('');
}

function fetchComments( id, showAllParams ) {
    var comments = elem.find('.joms-popup__comment');

    if ( !showAllParams ) {
        comments.empty();
    }

    joms.ajax({
        func: 'photos,ajaxSwitchPhotoTrigger',
        data: [ id, showAllParams ? 1 : 0 ],
        callback: function( json ) {
            var $tags;

            if ( !showAllParams ) {
                if ( json.comments && json.showall ) {
                    json.showall = '<div class="joms-comment__more joms-js--more-comments"><a href="javascript:">' + json.showall + '</a></div>';
                    json.comments = $( $.trim( json.comments ) );
                    json.comments.prepend( json.showall );
                }
            }

            if ( showAllParams ) {
                comments.find('.joms-comment').replaceWith( json.comments );
            } else {
                comments.html( json.head || '' );
                comments.append( json.comments );
                comments.append( json.form || '' );

                // Cache tag info.
                tags = json.tagged || [];
                tagLabel = json.tagLabel || '';
                tagRemoveLabel = json.tagRemoveLabel || '';

                // Update some flags if present
                canEdit = typeof json.can_edit === 'undefined' ? canEdit : json.can_edit;
                canDelete = typeof json.can_delete === 'undefined' ? canDelete : json.can_delete;
                canTag = typeof json.can_tag === 'undefined' ? canTag : json.can_tag;
                canMovePhoto = typeof json.can_move_photo === 'undefined' ? canMovePhoto : json.can_move_photo;
                canRotate = typeof json.can_rotate === 'undefined' ? canRotate : json.can_rotate;

                // Render description.
                comments.find('.joms-js--description').html(
                    renderDescription( json.description || {} )
                );

                // Update rotate buttons.
                toggleRotate( !!canRotate );

                // Render tag info.
                $tags = comments.find('.joms-js--tag-info');
                $tags.html( _tagBuildHtml() );

                comments
                    .find('.joms-js--comments,.joms-js--newcomment')
                    .find('textarea.joms-textarea');

                joms.fn.tagging.initInputbox();
            }

            updateDropdownHtml( json );
            initVideoPlayers();
        }
    });
}

function initVideoPlayers() {
    var cssInitialized = '.joms-js--initialized',
        cssVideos = '.joms-js--video',
        videos = $('.joms-comment__body,.joms-js--inbox').find( cssVideos ).not( cssInitialized );

    if ( !videos.length ) {
        return;
    }

    joms.loadCSS( joms.ASSETS_URL + 'vendors/mediaelement/mediaelementplayer.min.css' );
    videos.on( 'click.joms-video', cssVideos + '-play', function() {
        var $el = $( this ).closest( cssVideos );
        joms.util.video.play( $el, $el.data() );
    });

    if ( joms.ios ) {
        setTimeout(function() {
            videos.find( cssVideos + '-play' ).click();
        }, 2000 );
    }
}

function renderDescription( json ) {
    if ( typeof json !== 'object' ) {
        json = {};
    }

    return [
        '<div class="joms-js--btn-desc-content">', ( json.content || '' ), '</div>',
        '<div class="joms-js--btn-desc-editor joms-popup__hide">',
            '<textarea class="joms-textarea" style="margin:0" placeholder="', ( json.lang_placeholder || '' ), '">', br2nl( json.rawcontent || '' ), '</textarea>',
            '<div style="margin-top:5px;text-align:right">',
                '<button class="joms-button--neutral joms-button--small joms-js--btn-desc-cancel">', ( json.lang_cancel || 'Cancel' ), '</button> ',
                '<button class="joms-button--primary joms-button--small joms-js--btn-desc-save">', ( json.lang_save || 'Save' ), '</button>',
            '</div>',
        '</div>',
        '<div class="joms-js--btn-desc-edit"', ( canEdit ? '' : ' style="display:none"' ), '><a href="javascript:"',
            ' data-lang-add="', ( json.lang_add || 'Add description' ), '"',
            ' data-lang-edit="', ( json.lang_edit || 'Edit description' ), '">',
                ( json.rawcontent ? json.lang_edit : json.lang_add ),
            '</a>',
        '</div>'
    ].join('');
}

function br2nl( text ) {
    text = text || '';
    text = text.replace( /<br\s*\/?>/g, '\n' );
    return text;
}

function editDescription() {
    elem.find('.joms-js--btn-desc-content').hide();
    elem.find('.joms-js--btn-desc-edit').hide();
    elem.find('.joms-js--btn-desc-editor').show();
}

function cancelDescription() {
    elem.find('.joms-js--btn-desc-editor').hide();
    elem.find('.joms-js--btn-desc-content').show();
    elem.find('.joms-js--btn-desc-edit').show();
}

function saveDescription() {
    var content  = elem.find('.joms-js--btn-desc-content'),
        editor   = elem.find('.joms-js--btn-desc-editor'),
        button   = elem.find('.joms-js--btn-desc-edit'),
        textarea = editor.find('textarea'),
        value    = $.trim( textarea.val() );

    joms.ajax({
        func: 'photos,ajaxSaveCaption',
        data: [ id, value ],
        callback: function( json ) {
            var a = button.find('a');

            if ( json.error ) {
                window.alert( json.error );
                return;
            }

            if ( json.success ) {
                editor.hide();
                content.html( json.caption ).show();
                a.html( a.data( 'lang-' + ( value ? 'edit' : 'add' ) ) );
                button.show();
            }
        }
    });
}

// Exports.
return joms._.debounce(function( album, id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, album, id );
    });
}, 200 );

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.photo || (joms.popup.photo = {});
    joms.popup.photo.remove = factory( root, $ );

    define('popups/photo.remove',[ 'utils/popup' ], function() {
        return joms.popup.photo.remove;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'photos,ajaxConfirmRemovePhoto',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    joms.ajax({
        func: 'photos,ajaxRemovePhoto',
        data: [ id ],
        callback: function( json ) {
            var $photo;

            if ( json.error ) {
                elem.find('.joms-js--step1').hide();
                elem.find('.joms-js--step2').show().children().html( json.error || json.message );
            } else {
                $photo = $( '.joms-js--photo-' + id );
                if ( $photo.length && $photo.siblings().length ) {
                    $photo.remove();
                    cancel();
                } else {
                    window.location.reload();
                }
            }
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1', ( json.error ? ' joms-popup__hide' : '' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', json.message, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnCancel, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-js--step2', ( json.error ? '' : ' joms-popup__hide' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', ( json.error || '' ), '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.photo || (joms.popup.photo = {});
    joms.popup.photo.report = factory( root );

    define('popups/photo.report',[ 'utils/popup' ], function() {
        return joms.popup.photo.report;
    });

})( window, function() {

var popup, elem, id, url;

function render( _popup, _id, _url ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;
    url = _url;

    joms.ajax({
        func: 'system,ajaxReport',
        data: [],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'change', 'select', changeText );
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function changeText( e ) {
    elem.find('textarea').val( e.target.value );
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    var rTrim = /^\s+|\s+$/g,
        message;

    message = elem.find('textarea').val();
    message = message.replace( rTrim, '' );

    if ( !message ) {
        elem.find('.joms-js--error').show();
        return;
    }

    elem.find('.joms-js--error').hide();

    joms.ajax({
        func: 'system,ajaxSendReport',
        data: [ 'photos,reportPhoto', url || window.location.href, message, id ],
        callback: function( json ) {
            elem.find('.joms-js--step1').hide();
            elem.find('.joms-js--step2').show().children().html( json.error || json.message );
        }
    });
}


function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock joms-popup--500">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1', ( json.error ? ' joms-popup__hide' : '' ), '">',
            json.html,
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnCancel, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnSend, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-js--step2', ( json.error ? '' : ' joms-popup__hide' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', ( json.error || '' ), '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id, url ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id, url );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.photo || (joms.popup.photo = {});
    joms.popup.photo.setAvatar = factory( root, $ );

    define('popups/photo.setavatar',[ 'utils/popup' ], function() {
        return joms.popup.photo.setAvatar;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, id, url, params;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'photos,ajaxLinkToProfile',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            url = json.formUrl || '';
            params = json.formParams || {};

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    var form = $('<form method=post action="' + url + '" style="width:1px; height:1px; position:absolute"/>'),
        prop;

    for ( prop in params ) {
        form.append('<input type=hidden name="' + prop + '" value="' + params[prop] + '"/>');
    }

    form.appendTo( document.body );
    form[0].submit();
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1', ( json.error ? ' joms-popup__hide' : '' ), '">',
            '<div class="joms-popup__content">', json.message, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnCancel, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-js--step2', ( json.error ? '' : ' joms-popup__hide' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', ( json.error || '' ), '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.photo || (joms.popup.photo = {});
    joms.popup.photo.setCover = factory( root, $ );

    define('popups/photo.setcover',[ 'utils/popup' ], function() {
        return joms.popup.photo.setCover;
    });

})( window, joms.jQuery, function() {

var popup, elem, album, id;

function render( _popup, _album, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    album = _album;
    id = _id;

    joms.ajax({
        func: 'photos,ajaxConfirmDefaultPhoto',
        data: [ album, id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    joms.ajax({
        func: 'photos,ajaxSetDefaultPhoto',
        data: [ album, id ],
        callback: function( json ) {
            elem.find('.joms-js--step1').hide();
            elem.find('.joms-js--step2').show().children().html( json.error || json.message );
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1', ( json.error ? ' joms-popup__hide' : '' ), '">',
            '<div class="joms-popup__content">', json.message, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnNo, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-js--step2', ( json.error ? '' : ' joms-popup__hide' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', ( json.error || '' ), '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( album, id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, album, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.photo || (joms.popup.photo = {});
    joms.popup.photo.upload = factory( root, $ );

    define('popups/photo.upload',[ 'utils/loadlib', 'utils/popup' ], function() {
        return joms.popup.photo.upload;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, uploader, uploaderButton, uploaderPreview, albumid, contextid, context, lang, files, newalbumid;

function render( _popup, _albumid, _contextid, _context ) {
    var data;

    if ( elem ) elem.off();
    popup = _popup;
    albumid = _albumid || false;
    context = _context || false;
    contextid = _contextid || false;

    data = [];
    data.push( albumid || '' );
    data.push( contextid || '' );
    data.push( context || '' );

    joms.ajax({
        func: 'photos,ajaxUploadPhoto',
        data: data,
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            lang = json.lang || {};

            elem = popup.contentContainer;
            uploaderPreview = elem.find('.joms-gallery');

            files = [];

            elem.on( 'click', '.joms-tab__bar a', changeTab );
            elem.on( 'click', '.joms-js--form-toggle', toggleForm );
            elem.on( 'click', '.joms-js--btn-create', createAlbum );
            elem.on( 'click', '.joms-js--btn-add', upload );
            elem.on( 'click', '.joms-js--btn-view', viewAlbum );

            // Init uploader upon render.
            uploadInit();
        }
    });
}

function changeTab( e ) {
    var $btncreate = elem.find('.joms-js--btn-create'),
        $btnadd = elem.find('.joms-js--btn-add'),
        $el = $( e.target ),
        href = $el.attr('href');

    if ( newalbumid || href.match(/select-album/) ) {
        $btncreate.hide();
        $btnadd.show();
    } else {
        $btnadd.hide();
        $btncreate.show();
    }
}

function toggleForm( state ) {
    var $btn = elem.find('.joms-js--form-toggle');

    if ( state !== 'show' && state !== 'hide' ) {
        state = $btn.data('hidden') ? 'show' : 'hide';
    }

    if ( state === 'show' ) {
        $btn.removeData('hidden');
        elem.find('.joms-js--thumbnails').hide();
        elem.find('.joms-js--form-detail').css({ height: '' });
    } else if ( state === 'hide' ) {
        $btn.data('hidden', 'hidden');
        elem.find('.joms-js--form-detail').css({ height: 0 });
        elem.find('.joms-js--thumbnails').show();
    }
}

function createAlbum() {
    var album = $.trim( elem.find('[name=name]').val() ),
        location = $.trim( elem.find('[name=location]').val() ),
        description = $.trim( elem.find('[name=description]').val() ),
        permission = elem.find('[name=permissions]').val(),
        $albumerrormsg = elem.find('[name=name]').siblings('.joms-help').hide(),
        $loading = elem.find('.joms-js--btn-create img');

    if ( !album.length ) {
        $albumerrormsg.show();
        return;
    }

    if ( $loading.is(':visible') ) {
        return;
    }

    $loading.show();

    joms.ajax({
        func: 'photos,ajaxCreateAlbum',
        data: [ album, contextid || '', context || '', location || '', description || '', permission || '' ],
        callback: joms._.debounce(function( json ) {
            $loading.hide();

            if ( json.error ) {
                window.alert( json.error );
                return;
            }

            if ( json.albumid ) {
                albumid = newalbumid = json.albumid;
                elem.find('.joms-js--btn-create').hide();
                elem.find('.joms-js--btn-add').show();

                // Disable input elements on new album tab.
                elem.find('#joms-js__new-album').find('input.joms-input, textarea.joms-textarea, select.joms-select')
                    .attr('disabled', 'disabled');

                toggleForm('hide');
            }
        }, 500 )
    });
}

function upload() {
    uploadInit(function() {
        uploaderButton.click();
    });
}

function uploadInit( callback ) {
    if ( typeof callback !== 'function' ) {
        callback = function() {};
    }

    if ( uploader ) {
        callback();
        return;
    }

    joms.util.loadLib( 'plupload', function () {
        var container, button;

        container = $('<div id="joms-js--photoupload-uploader" aria-hidden="true" style="width:1px; height:1px; overflow:hidden">').appendTo( document.body );
        button    = $('<button id="joms-js--photoupload-uploader-button">').appendTo( container );
        uploader  = new window.plupload.Uploader({
            url: 'index.php?option=com_community&view=photos&task=multiUpload',
            filters: [{ title: 'Image files', extensions: 'jpg,jpeg,png,gif' }],
            container: 'joms-js--photoupload-uploader',
            browse_button: 'joms-js--photoupload-uploader-button',
            runtimes: 'html5,html4'
        });

        uploader.bind( 'FilesAdded', uploadAdded );
        uploader.bind( 'Error', uploadError );
        uploader.bind( 'UploadProgress', uploadProgress );
        uploader.bind( 'FileUploaded', uploadUploaded );
        uploader.bind( 'uploadComplete', uploadComplete );
        uploader.init();

        uploaderButton = container.find('input[type=file]');
        callback();
    });
}

function uploadAdded( up, files ) {
    var html = '',
        i;

    for ( i = 0; i < files.length; i++ ) {
        html += '<li class="joms-gallery__item joms-file--' + files[i].id + '">';
        html += '<div class="joms-gallery__thumbnail"><img src="' + joms.ASSETS_URL + 'photo_thumb.png"></div>';
        html += '<div class="joms-gallery__body">';
        html += '<a class="joms-gallery__title">' + files[i].name + '</a> <span>(' + Math.round( files[i].size / 1024 ) + ' KB)</span>';
        html += '<div class="joms-progressbar"><div class="joms-progressbar__progress" style="width:0%"></div></div>';
        html += '</div>';
        html += '</li>';
    }

    uploaderPreview.append( html );

    elem.find('.joms-js--btn-add').css({ visibility: 'visible' });
    elem.find('.joms-js--btn-view').hide();

    setTimeout(function() {
        uploadStartProxy();
    }, 1000);
}

function uploadStartProxy() {
    var $album = elem.find('[name=name]');
    if ( !$album.is(':visible') ) {
        albumid = elem.find('[name=album-id]').val();
    } else if ( newalbumid ) {
        albumid = newalbumid;
    }

    uploadStart();
}

function uploadStart() {
    elem.find('.joms-js--btn-add').css({ visibility: 'hidden' });
    elem.find('.joms-js--btn-view').hide();
    uploader.settings.url = joms.BASE_URL + 'index.php?option=com_community&view=photos&task=multiUpload&albumid=' + albumid;
    uploader.refresh();
    uploader.start();
}

function uploadError() {
}

function uploadProgress( up, file ) {
    var percent, bar;
    percent = Math.min( 100, Math.floor( file.loaded / file.size * 100 ) );
    bar = elem.find( '.joms-file--' + file.id );
    bar = bar.find( '.joms-progressbar__progress' );
    bar.stop().animate({ width: percent + '%' });
}

function uploadUploaded( up, file, resp ) {
    var json = {},
        item;

    try {
        json = JSON.parse( resp.response );
    } catch (e) {}

    if ( json.error ) {
        uploader.stop();
        if ( json.canContinue ) {
            elem.find('.joms-js--btn-add').css({ visibility: 'visible' });
        } else {
            elem.find('.joms-js--btn-add').css({ visibility: 'hidden' });
        }

        item = elem.find( '.joms-file--' + file.id );
        if ( item.prevAll().length ) {
            elem.find('.joms-js--btn-view').show();
        }
        item.nextAll().andSelf().remove();
        window.alert( json.msg );
        return;
    }

    if ( json.info ) {
        files.push({ photoId: json.photoId });
        item = elem.find( '.joms-file--' + file.id );
        item = item.find('img');
        item.attr( 'src', json.info );
        elem.find('.joms-js--btn-add').html( elem.find('.joms-js--btn-add').data('lang-more') );
        elem.find('.joms-js--btn-view').show();

        // Disable tabs and input elements if images are successfully uploaded.
        elem.off('click', '.joms-tab__bar a');
        elem.find('.joms-tab__bar a').removeAttr('href');
        elem.find('input.joms-input, textarea.joms-textarea, select.joms-select').attr('disabled', 'disabled');
    }
}

function uploadComplete() {
    elem.find('.joms-js--btn-add').css({ visibility: 'visible' });
    elem.find('.joms-js--btn-view').show();

    joms.ajax({
        func: 'photos,ajaxUpdateCounter',
        data: [ albumid, JSON.stringify({ files: files }) ],
        callback: function() {}
    });
}

function viewAlbum() {
    joms.ajax({
        func: 'photos,ajaxGetAlbumURL',
        data: [ albumid || '', contextid || '', context || '' ],
        callback: function( json ) {
            if ( json.url ) {
                window.location = json.url;
            }
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock joms-popup--photoupload">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        json.html,
        '</div>'
    ].join('');
}

// Exports.
return function( albumid, contextid, context ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, albumid, contextid, context );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.photo || (joms.popup.photo = {});
    joms.popup.photo.zoom = factory( root, $ );

    define('popups/photo.zoom',[ 'utils/popup' ], function() {
        return joms.popup.photo.zoom;
    });

})( window, joms.jQuery, function( window, $ ) {

var $cnt, $win;

function render( popup, url ) {
    var evtName = 'resize.joms-photozoom';

    popup.items[0] = {
        type: 'inline',
        src: buildHtml( url )
    };

    $cnt = popup.container;
    $win = $( window );

    // #719 Wait for image to be loaded.
    setTimeout(function() {
        $cnt.find( '.joms-popup img' ).one( 'load', fixResize ).each(function() {
            if ( this.complete ) $( this ).load();
        });
    }, 1 );

    $win.off( evtName )
        .on( evtName, fixResize );

    popup.updateItemHTML();
    popup.st.callbacks || (popup.st.callbacks = {});
    popup.st.callbacks.close = function() {
        $win.off( evtName );
    };
}

function buildHtml( url ) {
    url = url || '';
    url = url.replace( 'thumb_', '' );
    return [
        '<div class="joms-popup-wrapper" style="width:100%;height:100%;margin:0 auto;text-align:center">',
        '<div class="joms-popup" style="max-width:100%;left:auto;right:auto;position:relative;display:inline;top:0">',
        '<img src="' + url + '" style="width:auto;max-width:100%">',
        '</div>',
        '</div>'
    ].join('');
}

var fixResize = joms._.debounce(function() {
    var $pop = $cnt.find( '.joms-popup' ).css({ position: '', display: '', top: '', width: '' });
    var $img = $pop.find( 'img' ).css({ height: '' });

    // Unwrap from temporary wrapper.
    if ( $cnt.find( '.joms-popup-wrapper' ).length ) {
        $cnt.find( '.joms-popup-wrapper .mfp-close' ).appendTo( $pop );
        $pop.unwrap();
    }

    setTimeout(function() {
        var imgHeight = +$img.height(),
            cntHeight = +$cnt.height();

        imgHeight = imgHeight && ( imgHeight < cntHeight ) ? '' : cntHeight;
        $img.css( 'height', imgHeight );
        setTimeout(function() {
            $pop.css({ position: 'relative', width: $img.width() });
        }, 1 );
    }, 1 );
}, 100 );

// Exports.
return function( url ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, url );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.photo || (joms.popup.photo = {});
    joms.popup.photo.setAlbum = factory( root, $ );

    define('popups/photo.setalbum',[ 'utils/popup' ], function() {
        return joms.popup.photo.setAlbum;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    if ( Object.prototype.toString.call( id ) !== '[object Array]' ) {
        id = [ id ];
    }

    joms.ajax({
        func: 'photos,ajaxSetPhotoAlbum',
        data: [ id.join(',') ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    var albumid = elem.find('[name=albumid]').val();

    joms.ajax({
        func: 'photos,ajaxConfirmPhotoAlbum',
        data: [ albumid, id.join(',') ],
        callback: function( json ) {
            var message = [],
                $album, $photo, i;

            if ( json.message ) {
                message.push( json.message );
            }

            // Remove moved photos from album page.
            if ( json.moved && json.moved.length ) {
                for ( i = 0; i < json.moved.length; i++ ) {
                    $album = $( '.joms-js--album-' + json.moved[i].old_album );
                    if ( $album.length ) {
                        $photo = $album.find( '.joms-js--photo-' + json.moved[i].id );
                        $photo.remove();
                    }
                }
            }

            // Map errors.
            if ( Object.prototype.toString.call( json.error ) === '[object Array]' ) {
                if ( json.error.length ) {
                    for ( i = 0; i < json.error.length; i++ ) {
                        json.error[i] = '<li>ID: ' + json.error[i][0] + ' - ' + json.error[i][1] + '</li>';
                    }
                    message.push( '<ul>' + json.error.join('') + '</ul>' );
                }
            } else if ( typeof json.error === 'string' ) {
                message.push( json.error );
            }

            elem.find('.joms-js--step1').hide();
            elem.find('.joms-js--step2').show().children().html( message.join('<br/>') );
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1', ( json.error ? ' joms-popup__hide' : '' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', (json.html || ''), '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnCancel, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-js--step2', ( json.error ? '' : ' joms-popup__hide' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', ( json.error || '' ), '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.photo = factory( root, joms.popup.photo || {});

    define('popups/photo',[
        'popups/photo.open',
        'popups/photo.remove',
        'popups/photo.report',
        'popups/photo.setavatar',
        'popups/photo.setcover',
        'popups/photo.upload',
        'popups/photo.zoom',
        'popups/photo.setalbum'
    ], function() {
        return joms.popup.photo;
    });

})( window, function( window, sub ) {

// Exports.
return joms._.extend({}, sub );

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.pm || (joms.popup.pm = {});
    joms.popup.pm.send = factory( root );

    define('popups/pm.send',[ 'utils/popup' ], function() {
        return joms.popup.pm.send;
    });

})( window, function() {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'inbox,ajaxCompose',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;

            elem.find('textarea.joms-textarea').jomsTagging();

            elem.on( 'click', '[data-ui-object=popup-button-cancel]', cancel );
            elem.on( 'click', '[data-ui-object=popup-button-save]', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    var body = elem.find('[name=body]').val(),
        att = elem.find('.joms-textarea__attachment'),
        photo, file, attachment = {};

    body = body
        .replace( /\t/g, '\\t' )
        .replace( /\n/g, '\\n' )
        .replace( /&quot;/g,  '"' );

    if ( elem.data('saving') ) {
        return;
    }

    elem.data( 'saving', 1 );

    if ( att.is(':visible') ) {
        photo = att.find('.joms-textarea__attachment--thumbnail img');
        file = photo.siblings('b');
        if ( photo.is(':visible') && photo.attr('src') ) {
            attachment = {
                type: 'image',
                id: photo.data('photo_id')
            };
        } else if ( file.is(':visible') ) {
            attachment = {
                type: 'file',
                id: file.data('id')
            };
        }
    }

    joms.ajax({
        func: 'chat,ajaxPrivateMessageSend',
        data: [ id, body, JSON.stringify(attachment) ],
        callback: function( json ) {
            var step1 = elem.find('[data-ui-object=popup-step-1]'),
                step2 = elem.find('[data-ui-object=popup-step-2]');

            elem.removeData('saving');

            step2.find('[data-ui-object=popup-message]').html( json );
            step1.hide();
            step2.show();
        }
    });
}


function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock joms-popup--500">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div data-ui-object="popup-step-1"', ( json.error ? ' class="joms-popup__hide"' : '' ), '>',
            (json.html || ''),
            '<div class="joms-popup__action">',
            '<a href="javascript:" class="joms-button--neutral joms-button--small joms-left" data-ui-object="popup-button-cancel">', json.btnCancel, '</a> &nbsp;',
            '<button class="joms-button--primary joms-button--small" data-ui-object="popup-button-save">', json.btnSend, '</button>',
            '</div>',
        '</div>',
        '<div data-ui-object="popup-step-2"', ( json.error ? '' : ' class="joms-popup__hide"' ), '>',
            '<div class="joms-popup__content joms-popup__content--single" data-ui-object="popup-message">', (json.error || ''), '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.pm = factory( root, joms.popup.pm || {});

    define('popups/pm',[ 'popups/pm.send' ], function() {
        return joms.popup.pm;
    });

})( window, function( window, sub ) {

// Exports.
return joms._.extend({}, sub );

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.search || (joms.popup.search = {});
    joms.popup.search.save = factory( root, $ );

    define('popups/search.save',[],function() {
        return joms.popup.search.save;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, data;

function render( _popup, _data ) {
    var json, keys, key, values, value, i;

    if ( elem ) elem.off();
    popup = _popup;
    data = _data || {};
    json = data.json || {};
    keys = ( data.keys || '' ).split(',');
    values = [];

    for ( i = 0; i < keys.length; i++ ) {
        key = keys[i];

        if (( json['fieldType' + key] === 'date' ) || ( json['fieldType' + key] === 'birthdate' ) || ( json['condition' + key] === 'between' )) {
            value = json['value' + key] + ',' + json['value' + key + '_2'];
        } else {
            value = json['value' + key];
        }

        values[i] = [
            'field=' + json[ 'field' + key ] + ',' +
            'condition=' + json[ 'condition' + key ] + ',' +
            'fieldType=' + json[ 'fieldType' + key ] + ',' +
            'value=' + value
        ];
    }

    joms.ajax({
        func: 'memberlist,ajaxShowSaveForm',
        data: [ data.operator, data.avatar_only ? 1 : 0 ].concat( values ),
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    var $title = elem.find('[name=title]'),
        $description = elem.find('[name=description]'),
        error = false;

    if ( !$.trim( $title.val() ) ) {
        $title.siblings('.joms-help').show();
        error = true;
    } else {
        $title.siblings('.joms-help').hide();
    }

    if ( !$.trim( $description.val() ) ) {
        $description.siblings('.joms-help').show();
        error = true;
    } else {
        $description.siblings('.joms-help').hide();
    }

    if ( error ) {
        return;
    }

    elem.find('form').submit();
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1', ( json.error ? ' joms-popup__hide' : '' ), '">',
            '<div class="joms-popup__content">', json.html, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnCancel, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnSave, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-js--step2', ( json.error ? '' : ' joms-popup__hide' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', ( json.error || '' ), '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( data ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, data );
    });
};

});


(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.search = factory( root, joms.popup.search || {});

    define('popups/search',[ 'popups/search.save' ], function() {
        return joms.popup.search;
    });

})( window, function( window, sub ) {

// Exports.
return joms._.extend({}, sub );

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.tnc = factory( root );

})( window, function() {

var popup;

function render( _popup ) {
    popup = _popup;

    joms.ajax({
        func: 'register,ajaxShowTnc',
        data: [ 0 ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content joms-popup__content--single" style="max-height:400px;overflow:auto;">', ( json.html || '&nbsp;' ), '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function() {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp );
    });
};

});

define("popups/tnc", function(){});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.user || (joms.popup.user = {});
    joms.popup.user.changeVanityURL = factory( root, $ );

    define('popups/user.changevanityurl',[ 'utils/popup' ], function() {
        return joms.popup.user.changeVanityURL;
    });

})( window, joms.jQuery, function() {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'profile,ajaxUpdateURL',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '[data-ui-object=popup-button-cancel]', cancel );
            elem.on( 'click', '[data-ui-object=popup-button-save]', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    elem.find('form').submit();
}

function buildHtml( json ) {
    var action = '';

    json || (json = {});

    if ( json.btnUpdate && json.btnCancel ) {
        action = [
            '<div class="joms-popup__action">',
            '<a href="javascript:" class="joms-button--neutral joms-button--small joms-left" data-ui-object="popup-button-cancel">', json.btnCancel, '</a> &nbsp;',
            '<button class="joms-button--primary joms-button--small" data-ui-object="popup-button-save">', json.btnUpdate, '</button>',
            '</div>'
        ].join('');
    }

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content">', ( json.html || json.message ), '</div>',
        action,
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.user || (joms.popup.user = {});
    joms.popup.user.addFeatured = factory( root, $ );

    define('popups/user.addfeatured',[ 'utils/popup' ], function() {
        return joms.popup.user.addFeatured;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'search,ajaxAddFeatured',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.st.callbacks || (popup.st.callbacks = {});
            popup.st.callbacks.close = function() {
                window.location.reload();
            };

            popup.updateItemHTML();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.html || '' ), '</div>',
        '<div class="joms-popup__action">',
        '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.user || (joms.popup.user = {});
    joms.popup.user.removeFeatured = factory( root, $ );

    define('popups/user.removefeatured',[ 'utils/popup' ], function() {
        return joms.popup.user.removeFeatured;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'search,ajaxRemoveFeatured',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.st.callbacks || (popup.st.callbacks = {});
            popup.st.callbacks.close = function() {
                window.location.reload();
            };

            popup.updateItemHTML();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.html || '' ), '</div>',
        '<div class="joms-popup__action">',
        '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.user || (joms.popup.user = {});
    joms.popup.user.ban = factory( root, $ );

    define('popups/user.ban',[ 'utils/popup' ], function() {
        return joms.popup.user.ban;
    });

})( window, joms.jQuery, function() {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'profile,ajaxBanUser',
        data: [ id, 0 ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '[data-ui-object=popup-button-cancel]', cancel );
            elem.on( 'click', '[data-ui-object=popup-button-save]', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    elem.find('form').submit();
}

function buildHtml( json ) {
    var action = '';

    json || (json = {});

    if ( !json.error ) {
        action = [
            '<div class="joms-popup__action">',
            '<a href="javascript:" class="joms-button--neutral joms-button--small joms-left" data-ui-object="popup-button-cancel">', json.btnNo, '</a> &nbsp;',
            '<button class="joms-button--primary joms-button--small" data-ui-object="popup-button-save">', json.btnYes, '</button>',
            '</div>',
        ].join('');
    }

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content', ( json.error ? ' joms-popup__content--single' : '' ), '">', ( json.html || json.error ), '</div>',
        action,
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.user || (joms.popup.user = {});
    joms.popup.user.unban = factory( root, $ );

    define('popups/user.unban',[ 'utils/popup' ], function() {
        return joms.popup.user.unban;
    });

})( window, joms.jQuery, function() {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'profile,ajaxBanUser',
        data: [ id, 1 ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '[data-ui-object=popup-button-cancel]', cancel );
            elem.on( 'click', '[data-ui-object=popup-button-save]', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    elem.find('form').submit();
}

function buildHtml( json ) {
    var action = '';

    json || (json = {});

    if ( !json.error ) {
        action = [
            '<div class="joms-popup__action">',
            '<a href="javascript:" class="joms-button--neutral joms-button--small joms-left" data-ui-object="popup-button-cancel">', json.btnNo, '</a> &nbsp;',
            '<button class="joms-button--primary joms-button--small" data-ui-object="popup-button-save">', json.btnYes, '</button>',
            '</div>',
        ].join('');
    }

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content', ( json.error ? ' joms-popup__content--single' : '' ), '">', ( json.html || json.error ), '</div>',
        action,
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.user || (joms.popup.user = {});
    joms.popup.user.block = factory( root, $ );

    define('popups/user.block',[ 'utils/popup' ], function() {
        return joms.popup.user.block;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'profile,ajaxConfirmBlockUser',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    joms.ajax({
        func: 'profile,ajaxBlockUser',
        data: [ id ],
        callback: function( json ) {
            if ( !json.success ) {
                elem.find('.joms-popup__action').hide();
                elem.find('.joms-popup__content').html( json.error );
                return;
            }

            popup.close();
            window.location.reload();
        }
    });
}

function buildHtml( json ) {
    var action;

    json || (json = {});

    if ( json.error ) {
        action = [
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-js--button-cancel">', json.btnClose, '</button>',
            '</div>'
        ].join('');
    } else {
        action = [
            '<div class="joms-popup__action">',
            '<a href="javascript:" class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnNo, '</a> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnYes, '</button>',
            '</div>'
        ].join('');
    }

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content">', ( json.error || json.html || json.message ), '</div>',
        action,
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.user || (joms.popup.user = {});
    joms.popup.user.unblock = factory( root, $ );

    define('popups/user.unblock',[ 'utils/popup' ], function() {
        return joms.popup.user.unblock;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'profile,ajaxConfirmUnBlockUser',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '[data-ui-object=popup-button-cancel]', cancel );
            elem.on( 'click', '[data-ui-object=popup-button-save]', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    joms.ajax({
        func: 'profile,ajaxUnblockUser',
        data: [ id ],
        callback: function( json ) {
            if ( !json.success ) {
                elem.find('.joms-popup__action').hide();
                elem.find('.joms-popup__content').html( json.error );
                return;
            }

            popup.close();
            window.location.reload();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content">', ( json.html || json.message ), '</div>',
        '<div class="joms-popup__action">',
        '<a href="javascript:" class="joms-button--neutral joms-button--small joms-left" data-ui-object="popup-button-cancel">', json.btnNo, '</a> &nbsp;',
        '<button class="joms-button--primary joms-button--small" data-ui-object="popup-button-save">', json.btnYes, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.user || (joms.popup.user = {});
    joms.popup.user.ignore = factory( root, $ );

    define('popups/user.ignore',[ 'utils/popup' ], function() {
        return joms.popup.user.ignore;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'profile,ajaxConfirmIgnoreUser',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '[data-ui-object=popup-button-cancel]', cancel );
            elem.on( 'click', '[data-ui-object=popup-button-save]', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    joms.ajax({
        func: 'profile,ajaxIgnoreUser',
        data: [ id ],
        callback: function( json ) {
            if ( !json.success ) {
                elem.find('.joms-popup__action').hide();
                elem.find('.joms-popup__content').html( json.error );
                return;
            }

            popup.close();
            window.location.reload();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content">', ( json.html || json.message ), '</div>',
        '<div class="joms-popup__action">',
        '<a href="javascript:" class="joms-button--neutral joms-button--small joms-left" data-ui-object="popup-button-cancel">', json.btnNo, '</a> &nbsp;',
        '<button class="joms-button--primary joms-button--small" data-ui-object="popup-button-save">', json.btnYes, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.user || (joms.popup.user = {});
    joms.popup.user.report = factory( root );

    define('popups/user.report',[ 'utils/popup' ], function() {
        return joms.popup.user.report;
    });

})( window, function() {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'system,ajaxReport',
        data: [],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'change', 'select', changeText );
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function changeText( e ) {
    elem.find('textarea').val( e.target.value );
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    var rTrim = /^\s+|\s+$/g,
        message;

    message = elem.find('textarea').val();
    message = message.replace( rTrim, '' );

    if ( !message ) {
        elem.find('.joms-js--error').show();
        return;
    }

    elem.find('.joms-js--error').hide();

    joms.ajax({
        func: 'system,ajaxSendReport',
        data: [ 'profile,reportProfile', window.location.href, message, id ],
        callback: function( json ) {
            elem.find('.joms-js--step1').hide();
            elem.find('.joms-js--step2').show().children().html( json.error || json.message );
        }
    });
}


function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock joms-popup--500">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1', ( json.error ? ' joms-popup__hide' : '' ), '">',
            json.html,
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnCancel, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnSend, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-js--step2', ( json.error ? '' : ' joms-popup__hide' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', ( json.error || '' ), '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.user || (joms.popup.user = {});
    joms.popup.user.unignore = factory( root, $ );

    define('popups/user.unignore',[ 'utils/popup' ], function() {
        return joms.popup.user.unignore;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'profile,ajaxConfirmUnIgnoreUser',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '[data-ui-object=popup-button-cancel]', cancel );
            elem.on( 'click', '[data-ui-object=popup-button-save]', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    joms.ajax({
        func: 'profile,ajaxUnIgnoreUser',
        data: [ id ],
        callback: function( json ) {
            if ( !json.success ) {
                elem.find('.joms-popup__action').hide();
                elem.find('.joms-popup__content').html( json.error );
                return;
            }

            popup.close();
            window.location.reload();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content">', ( json.html || json.message ), '</div>',
        '<div class="joms-popup__action">',
        '<a href="javascript:" class="joms-button--neutral joms-button--small joms-left" data-ui-object="popup-button-cancel">', json.btnNo, '</a> &nbsp;',
        '<button class="joms-button--primary joms-button--small" data-ui-object="popup-button-save">', json.btnYes, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.user = factory( root, joms.popup.user || {});

    define('popups/user',[
        'popups/user.changevanityurl',
        'popups/user.addfeatured',
        'popups/user.removefeatured',
        'popups/user.ban',
        'popups/user.unban',
        'popups/user.block',
        'popups/user.unblock',
        'popups/user.ignore',
        'popups/user.report',
        'popups/user.unignore'
    ], function() {
        return joms.popup.user;
    });

})( window, function( window, sub ) {

// Exports.
return joms._.extend({}, sub );

});


(function( root, $, factory ) {

    joms.util || (joms.util = {});
    joms.util.videotag = factory( root, $ );

})( window, joms.jQuery, function( window, $, undef ) {

var wrapper, elem, img, friends, callback, tagAdded, noResult;

function create( e, tags, groupid, eventid ) {
    var pos, top, left, width, height;

    destroy();

    wrapper  = $( buildHtml() );
    elem     = wrapper.find('.joms-phototag');
    img      = e && e.currentTarget ? $( e.currentTarget ).closest('.joms-popup--video').find('iframe,video,.joms-js--video').eq(0) : $( e );
    friends  = undef;
    callback = {};
    width    = img.width();
    height   = img.height();
    pos      = img.position();
    top      = pos.top;
    left     = pos.left;
    tagAdded = tags || [];

    elem.css({
        top: 0,
        left: 0
    });

    wrapper.css({
        top: top,
        left: left,
        width: width,
        height: height
    });

    wrapper.insertBefore( img );

    pos = calcClickPosition( e );

    elem.css({ top: pos.top, left: pos.left });
    elem.on( 'keyup', 'input', filter );
    elem.on( 'click', 'a[data-id]', select );
    elem.on( 'click', 'button', destroy );
    elem.on( 'click', function( e ) {
        e.stopPropagation();
    });
    wrapper.on( 'click', moveBoxPosition );

    if ( +groupid ) {
        joms.fn.tagging.fetchGroupMembers( groupid, function( members ) {
            friends = members;
            filter();
        });
    } else if ( +eventid ) {
        joms.fn.tagging.fetchEventMembers( eventid, function( members ) {
            friends = members;
            filter();
        });
    } else {
        filter();
    }

    if ( joms.ios ) {
        try {
            window.scrollTo( window.scrollLeft, elem.find('input').offset().top - 100 );
        } catch (e) {}
    }

    // Apparently Android (Chrome?) trigger "onresize" event when keypad being shown,
    // which make phototag immediately closed. Add resize handler only on desktop browser.
    if ( !joms.mobile ) {
        $( window ).on( 'resize.phototag', destroy );
    }
}

function filter( e ) {
    var input, keyword, filtered, ac;

    if ( !friends ) {
        friends = window.joms_friends || [];
    }

    input = $( e ? e.currentTarget : elem.find('input') );
    keyword = input.val().replace( /^\s+|\s+$/g, '' ).toLowerCase();
    filtered = friends;

    filtered = joms._.filter( friends, function( obj ) {
        if ( !obj ) return false;
        if ( !obj.name ) return false;
        if ( tagAdded && tagAdded.indexOf( obj.id + '' ) >= 0 ) return false;
        if ( keyword && obj.name.toLowerCase().indexOf( keyword ) < 0 ) return false;
        return true;
    });

    filtered = filtered.slice(0, 8);
    filtered = joms._.map( filtered, function( obj ) {
        return '<a href="javascript:" data-id="' + obj.id + '">' + obj.name + '</a>';
    });

    if ( !filtered.length ) {
        filtered = [ '<span><em>' + window.joms_lang.COM_COMMUNITY_NO_RESULT_FOUND + '</em></span>' ];
        noResult = true;
    } else {
        noResult = false;
    }

    ac = elem.find('.joms-phototag__autocomplete');
    ac.html( filtered.join('') );

    ac.append(
        '<div><button class="joms-button--neutral joms-button--small joms-button--full">' +
        window.joms_lang.COM_COMMUNITY_PHOTO_DONE_TAGGING +
        '</button></div>'
    );

    ac.show();
}

function select( e ) {
    var ac = elem.find('.joms-phototag__autocomplete'),
        el = $( e.currentTarget ),
        id = el.data('id') || '',
        pos;

    e.stopPropagation();
    ac.hide();

    if ( callback && callback.tagAdded ) {
        tagAdded || (tagAdded = []);
        tagAdded.push( id + '' );
        pos = calcBoxPosition();

        callback.tagAdded(
            id,
            pos.left,
            pos.top,
            pos.width,
            pos.height
        );

        filter();
    }
}

function destroy() {
    if ( elem ) {
        elem.remove();
        wrapper.remove();
        $( window ).off('resize.phototag');
        elem = undef;
        img = undef;

        if ( callback && callback.destroy ) {
            callback.destroy();
        }

        callback = undef;
    }
}

function on( eventType, fn ) {
    callback[ eventType ] = fn;
}

function off( eventType ) {
    if ( !eventType ) {
        callback = {};
    } else if ( callback[ eventType ] ) {
        callback[ eventType ] = undef;
    }
}

function calcClickPosition() {
    var height = img.height(),
        width  = img.width(),
        left, top;

    // Respect wrapper boundaries.
    top  = Math.max( 0, height - 86 ) / 4.5;
    left = Math.max( 0, width - 86 ) / 2;

    return {
        top: top,
        left: left
    };
}

function calcBoxPosition() {
    var pos, ctWidth, ctHeight, boxWidth, boxHeight, boxLeft, boxTop;

    ctWidth = wrapper.width();
    ctHeight = wrapper.height();

    pos = elem.position();
    boxWidth = elem.width();
    boxHeight = elem.height();
    boxLeft = pos.left + boxWidth / 2;
    boxTop = pos.top + boxHeight / 2;

    // Percentage (relative to wrapper height).
    boxWidth = boxWidth / ctHeight;
    boxHeight = boxHeight / ctHeight;

    // Percentage (relative to wrapper dimension).
    boxLeft = boxLeft / ctWidth;
    boxTop = boxTop / ctHeight;

    return {
        top    : boxTop,
        left   : boxLeft,
        width  : boxWidth,
        height : boxHeight
    };
}

function moveBoxPosition( e ) {
    var pos;

    if ( noResult ) {
        destroy();
        return;
    }

    pos = calcClickPosition( e );

    elem.css({
        top: pos.top,
        left: pos.left
    });
}

function buildHtml() {
    return [
        '<div class=joms-phototag__wrapper>',
        '<div class=joms-phototag>',
        '<div class=joms-phototag__input>',
        '<input type=text placeholder="', window.joms_lang.COM_COMMUNITY_SEARCH,'">',
        '<div class="joms-phototag__autocomplete"></div>',
        '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return {
    create: create,
    destroy: destroy,
    on: on,
    off: off
};

});

define("utils/videotag", function(){});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.video || (joms.popup.video = {});
    joms.popup.video.open = factory( root, $ );

    define('popups/video.open',[ 'utils/popup', 'utils/videotag' ], function() {
        return joms.popup.video.open;
    });

})( window, joms.jQuery, function( window, $ ) {

var iconCog      = '<svg viewBox="0 0 16 16" class="joms-icon"><use xlink:href="#joms-icon-cog"></use></svg>',
    iconBubble   = '<svg viewBox="0 0 16 16" class="joms-icon"><use xlink:href="#joms-icon-bubble"></use></svg>',
    iconThumbsUp = '<svg viewBox="0 0 16 16" class="joms-icon"><use xlink:href="#joms-icon-thumbs-up"></use></svg>',
    iconTag      = '<svg viewBox="0 0 16 16" class="joms-icon"><use xlink:href="#joms-icon-tag"></use></svg>',

    popup, elem, tagBtn, tags, tagLabel, tagRemoveLabel, id, lang,
    canEdit, canDelete, videoUrl, userId, groupId, eventId, isRegistered, isOwner, isAdmin, enableProfileVideo, enableReporting, enableSharing, enableFeature;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'videos,ajaxShowVideoWindow',
        data: [ id ],
        callback: function( json ) {
            json || (json = {});
            lang = json.lang || {};
            canEdit = json.can_edit || false;
            canDelete = json.can_delete || false;
            videoUrl = json.video_url;

            // Priviliges.
            isRegistered = userId = +json.my_id;
            groupId = +json.groupid;
            eventId = +json.eventid;
            isOwner = isRegistered && ( +json.my_id === +json.owner_id );
            isAdmin = +json.is_admin;

            // Settings.
            enableProfileVideo = +json.enableprofilevideo;
            enableReporting = +json.enablereporting;
            enableSharing = +json.enablesharing;
            enableFeature = json.enablefeature;

            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            // Override popup#close function.
            popup.close = closeOverride;

            initVideo();

            elem = popup.contentContainer;
            tagBtn = elem.find('.joms-popup__btn-tag-video');

            elem.on( 'click', '.joms-popup__btn-tag-video', tagPrepare );
            elem.on( 'click', '.joms-popup__btn-comments', toggleComments );
            elem.on( 'click', '.joms-popup__btn-option', toggleDropdown );
            elem.on( 'click', '.joms-popup__btn-share', share );
            elem.on( 'click', '.joms-popup__btn-report', report );
            elem.on( 'click', '.joms-popup__btn-fetch', _fetch );
            elem.on( 'click', '.joms-popup__btn-profile', setAsProfileVideo );
            elem.on( 'click', '.joms-popup__btn-feature', feature );
            elem.on( 'click', '.joms-popup__btn-edit', _edit );
            elem.on( 'click', '.joms-popup__btn-delete', _delete );
            elem.on( 'mouseleave', '.joms-popup__dropdown--wrapper', hideDropdown );
            elem.on( 'click', '.joms-js--remove-tag', tagRemove );
            elem.on( 'click', '.joms-js--btn-desc-toggle', toggleDescription );
            elem.on( 'click', '.joms-js--btn-desc-edit', editDescription );
            elem.on( 'click', '.joms-js--btn-desc-cancel', cancelDescription );
            elem.on( 'click', '.joms-js--btn-desc-save', saveDescription );

            fetchComments( id );
        }
    });
}

function stripTags( html ) {
    html = html.replace( /<\/?[^>]+>/g, '' );
    return html;
}

function buildHtml( json ) {
    var playerHtml;

    json || (json = {});
    playerHtml = json.error || json.playerHtml || '';

    return [
        '<div class="joms-popup joms-popup--video">',
        '<div class="joms-popup__commentwrapper">',
        '<div class="joms-popup__content">',
        '<div class="joms-popup__video">',
        playerHtml,
        '</div>',
        '<div class="joms-popup__option clearfix">',
        '<div class="joms-popup__optcaption">',
        ' &nbsp;<svg viewBox="0 0 16 16" class="joms-icon"><use xlink:href="#joms-icon-eye"></use></svg> ',
        json.hits,
        '</div>',
        '<div class="joms-popup__optoption">',
        '<button class="joms-popup__btn-comments">', iconBubble, ' <span class="joms-popup__btn-overlay">', lang.comments, '</span></button>',
        getLikeHtml( json.like ),
        ( canEdit ? '<button class="joms-popup__btn-tag-video">' + iconTag + ' <span class="joms-popup__btn-overlay">' + lang.tag_video + '</span></button>' : '' ),
        '<div class="joms-popup__dropdown--wrapper">', updateDropdownHtml( json ), '<button class="joms-popup__btn-option">', iconCog, ' <span class="joms-popup__btn-overlay">', lang.options, '</span></button></div>',
        '</div>',
        '</div>',
        '</div>',
        '<div class="joms-popup__comment"></div>',
        '<button class="mfp-close" type="button" title="Close (Esc)">×</button>',
        '</div>',
        '</div>'
    ].join('');
}

function getLikeHtml( json ) {
    var html, count;

    // Like info.
    html = '';
    if ( json ) {
        html += '<button class="joms-popup__btn-like joms-js--like-videos-' + id + ( json.is_liked ? ' liked' : '' ) + '"';
        html += ' onclick="joms.api.page' + ( json.is_liked ? 'Unlike' : 'Like' ) + '(\'videos\', \'' + id + '\');"';
        html += ' data-lang="' + ( json.lang || 'Like' ) + '"';
        html += ' data-lang-like="' + ( json.lang_like || 'Like' ) + '"';
        html += ' data-lang-liked="' + ( json.lang_liked || 'Liked' ) + '">';
        html += iconThumbsUp + ' ';
        html += '<span>';
        html += ( json.is_liked ? json.lang_liked : json.lang_like );

        count = +json.count;
        if ( count > 0 ) {
            html += ' (' + count + ')';
        }

        html += '</span></button>';
    }

    return html;
}

function tagPrepare( e ) {
    if ( tagBtn.data('tagging') ) {
        tagBtn.removeData('tagging');
        tagBtn.html( iconTag + ' <span class="joms-popup__btn-overlay">' + lang.tag_video + '</span>' );
        tagCancel();
    } else {
        tagBtn.data( 'tagging', 1 );
        tagBtn.html( iconTag + ' ' + lang.done_tagging );
        tagStart( e );
    }
}

function tagStart( e ) {
    var indices = joms._.map( tags, function( item ) {
        return item.userId + '';
    });

    joms.util.videotag.create( e, indices, groupId, eventId );
    joms.util.videotag.on( 'tagAdded', tagAdded );
    joms.util.videotag.on( 'destroy', function() {
        tagBtn.removeData('tagging');
        tagBtn.html( iconTag + ' <span class="joms-popup__btn-overlay">' + lang.tag_video + '</span>' );
    });
}

function tagAdded( userId ) {
    joms.ajax({
        func: 'videos,ajaxAddVideoTag',
        data: [ id, userId ],
        callback: function( json ) {
            var $comments, $tags;

            if ( json.error ) {
                window.alert( stripTags( json.error ) );
                return;
            }

            if ( json.success ) {
                tags.push( json.data );

                // Render tag info.
                $comments = elem.find('.joms-popup__comment');
                $tags = $comments.find('.joms-js--tag-info');
                $tags.html( _tagBuildHtml() );
            }
        }
    });
}

function tagCancel() {
    joms.util.videotag.destroy();
}

function tagRemove( e ) {
    var el = $( e.currentTarget ),
        userId = el.data('id');

    joms.ajax({
        func: 'videos,ajaxRemoveVideoTag',
        data: [ id, userId ],
        callback: function( json ) {
            var $comments, $tags, i;

            if ( json.error ) {
                window.alert( stripTags( json.error ) );
                return;
            }

            if ( json.success ) {
                for ( i = 0; i < tags.length; i++ ) {
                    if ( +userId === +tags[i].userId ) {
                        tags.splice( i--, 1 );
                    }
                }

                // Render tag info.
                $comments = elem.find('.joms-popup__comment');
                $tags = $comments.find('.joms-js--tag-info');
                $tags.html( _tagBuildHtml() );
            }

        }
    });
}

function _tagBuildHtml() {
    var html, item, str, i;

    if ( !tags || !tags.length ) {
        return '';
    }

    html = [];

    for ( i = 0; i < tags.length; i++ ) {
        item = tags[i];
        str = '<a href="' + item.profileUrl + '">' + item.displayName + '</a>';

        if ( item.canRemove ) {
            str += ' (<a href="javascript:" class="joms-js--remove-tag" data-id="' + item.userId + '">' + tagRemoveLabel + '</a>)';
        }

        html.push( str );
    }

    html = html.join(', ');
    html = tagLabel + '<br>' + html;

    return html;
}

function toggleComments() {
    elem.children('.joms-popup').toggleClass('joms-popup--togglecomment');
}

function closeOverride() {
    var $ct = elem.children('.joms-popup'),
        className = 'joms-popup--togglecomment';

    if ( $ct.hasClass( className ) ) {
        $ct.removeClass( className );
        return;
    }

    $.magnificPopup.proto.close.call( this );
}

function toggleDropdown( e ) {
    var wrapper = $( e.target ).closest('.joms-popup__dropdown--wrapper'),
        dropdown = wrapper.children('.joms-popup__dropdown');

    dropdown.toggleClass('joms-popup__dropdown--open');
}

function hideDropdown( e ) {
    var wrapper = $( e.target ).closest('.joms-popup__dropdown--wrapper'),
        dropdown = wrapper.children('.joms-popup__dropdown');

    dropdown.removeClass('joms-popup__dropdown--open');
}

function updateDropdownHtml( json ) {
    var html = '';

    json || (json = {});

    if ( enableSharing ) {
        html += '<a href="javascript:" class="joms-popup__btn-share">' + lang.share + '</a>';
        html += '<div class="sep"></div>';
    }

    if ( isOwner || isAdmin ) {
        html += '<a href="javascript:" class="joms-popup__btn-fetch">' + lang.fetch + '</a>';
        html += ( isOwner && enableProfileVideo ? '<a href="javascript:" class="joms-popup__btn-profile">' + lang.set_as_profile_video + '</a>' : '' );
        if ( isAdmin && enableFeature ) {
            if ( enableFeature === 'add' ) {
                html += '<a href="javascript:" class="joms-popup__btn-feature" data-featured="0">' + lang.make_feature + '</a>';
            } else if ( enableFeature === 'remove' ) {
                html += '<a href="javascript:" class="joms-popup__btn-feature" data-featured="1">' + lang.remove_featured + '</a>';
            }
        }
        html += '<div class="sep"></div>';
        html += ( canEdit ? '<a href="javascript:" class="joms-popup__btn-edit">' + lang.edit_video + '</a>' : '' );
        html += ( canDelete ? '<a href="javascript:" class="joms-popup__btn-delete">' + lang.delete_video + '</a>' : '' );
    } else {
        html += ( canEdit ? '<a href="javascript:" class="joms-popup__btn-edit">' + lang.edit_video + '</a>' : '' );
        html += ( canDelete ? '<a href="javascript:" class="joms-popup__btn-delete">' + lang.delete_video + '</a>' : '' );
        html += ( enableReporting ? '<a href="javascript:" class="joms-popup__btn-report">' + lang.report + '</a>' : '' );
    }

    html = '<div class="joms-popup__dropdown"><div class="joms-popup__ddcontent">' + html + '</div></div>';

    return html;
}

function share() {
    joms.api.pageShare( videoUrl );
}

function report() {
    joms.api.videoReport( userId, videoUrl );
}

function _fetch() {
    joms.api.videoFetchThumbnail( id );
}

function setAsProfileVideo() {
    joms.ajax({
        func: 'profile,ajaxConfirmLinkProfileVideo',
        data: [ id ],
        callback: function( json ) {
            if ( json.error ) {
                window.alert( stripTags( json.error ) );
                return;
            }

            if ( window.confirm( stripTags( json.html ) ) ) {
                setAsProfileVideoConfirm();
            }
        }
    });
}

function setAsProfileVideoConfirm() {
    joms.ajax({
        func: 'profile,ajaxLinkProfileVideo',
        data: [ id ],
        callback: function( json ) {
            if ( json.error ) {
                window.alert( stripTags( json.error ) );
                return;
            }

            window.alert( stripTags( json.message ) );
            setTimeout(function() {
                window.location.reload();
            }, 500 );
        }
    });
}

function feature( e ) {
    var $btn = $( e.currentTarget ),
        isFeatured = +$btn.data('featured');

    if ( isFeatured ) {
        joms.api.videoRemoveFeatured( id );
    } else {
        joms.api.videoAddFeatured( id );
    }
}

function _edit() {
    joms.api.videoEdit( id );
}

function _delete() {
    joms.ajax({
        func: 'videos,ajaxConfirmRemoveVideo',
        data: [ id ],
        callback: function( json ) {
            if ( json.error ) {
                window.alert( stripTags( json.error ) );
                return;
            }

            if ( window.confirm( stripTags( json.html ) ) ) {
                _deleteConfirm();
            }
        }
    });
}

function _deleteConfirm() {
    joms.ajax({
        func: 'videos,ajaxRemoveVideo',
        data: [ id ],
        callback: function( json ) {
            if ( json.error ) {
                window.alert( stripTags( json.error ) );
                return;
            }

            window.alert( stripTags( json.message ) );
            setTimeout(function() {
                window.location.reload();
            }, 500 );
        }
    });
}

function fetchComments( id, showAllParams ) {
    var comments = elem.find('.joms-popup__comment');

    if ( !showAllParams ) {
        comments.empty();
    }

    joms.ajax({
        func: 'videos,ajaxGetInfo',
        data: [ id, showAllParams ? 1 : 0 ],
        callback: function( json ) {
            var $tags;

            if ( !showAllParams ) {
                if ( json.comments && json.showall ) {
                    json.showall = '<div class="joms-comment__more joms-js--more-comments"><a href="javascript:">' + json.showall + '</a></div>';
                    json.comments = $( json.comments );
                    json.comments.prepend( json.showall );
                }
            }

            if ( showAllParams ) {
                comments.find('.joms-comment').replaceWith( json.comments );
            } else {
                comments.html( json.head || '' );
                comments.append( json.comments );
                comments.append( json.form || '' );

                // Render description.
                comments.find('.joms-js--description').html(
                    renderDescription( json.description || {} )
                );

                // Cache tag info.
                tags = json.tagged || [];
                tagLabel = json.tagLabel || '';
                tagRemoveLabel = json.tagRemoveLabel || '';

                // Render tag info.
                $tags = comments.find('.joms-js--tag-info');
                $tags.html( _tagBuildHtml() );

                comments.find('textarea.joms-textarea');
                joms.fn.tagging.initInputbox();
            }

            initVideoPlayers();
        }
    });
}

function initVideo() {
    var cssVideo = '.joms-js--video',
        video = $('.joms-popup__content').find( cssVideo );

    if ( !video.length ) {
        return;
    }

    joms.loadCSS( joms.ASSETS_URL + 'vendors/mediaelement/mediaelementplayer.min.css' );
    video.on( 'click.joms-video', cssVideo + '-play', function() {
        var $el = $( this ).closest( cssVideo );
        joms.util.video.play( $el, $el.data() );
    });
}

function initVideoPlayers() {
    var initialized = '.joms-js--initialized',
        cssVideos = '.joms-js--video',
        videos = $('.joms-comment__body,.joms-js--inbox').find( cssVideos ).not( initialized ).addClass( initialized.substr(1) );

    if ( !videos.length ) {
        return;
    }

    joms.loadCSS( joms.ASSETS_URL + 'vendors/mediaelement/mediaelementplayer.min.css' );
    videos.on( 'click.joms-video', cssVideos + '-play', function() {
        var $el = $( this ).closest( cssVideos );
        joms.util.video.play( $el, $el.data() );
    });

    if ( joms.ios ) {
        setTimeout(function() {
            videos.find( cssVideos + '-play' ).click();
        }, 2000 );
    }
}

function renderDescription( json ) {
    var showExcerpt;

    if ( typeof json !== 'object' ) {
        json = {};
    }

    if ( json.content ) {
        if ( json.excerpt !== json.rawcontent ) {
            showExcerpt = true;
        }
    }

    return [
        '<div class="joms-js--btn-desc-content">',
            '<span class="joms-js--btn-desc-excerpt"', ( showExcerpt ? '' : ' style="display:none"' ), '>', ( json.excerpt || '' ), '</span>',
            '<span class="joms-js--btn-desc-fulltext"', ( showExcerpt ? ' style="display:none"' : '' ), '>', ( json.content || '' ), '</span>',
            ' <a href="javascript:" class="joms-js--btn-desc-toggle"', ( showExcerpt ? '' : ' style="display:none"' ), '>', window.joms_lang.COM_COMMUNITY_SHOW_MORE, '</a>',
        '</div>',
        '<div class="joms-js--btn-desc-editor joms-popup__hide">',
            '<textarea class="joms-textarea" style="margin:0" placeholder="', ( json.lang_placeholder || '' ), '">', br2nl( json.content || '' ), '</textarea>',
            '<div style="margin-top:5px;text-align:right">',
                '<button class="joms-button--neutral joms-button--small joms-js--btn-desc-cancel">', ( json.lang_cancel || 'Cancel' ), '</button> ',
                '<button class="joms-button--primary joms-button--small joms-js--btn-desc-save">', ( json.lang_save || 'Save' ), '</button>',
            '</div>',
        '</div>',
        '<div class="joms-js--btn-desc-edit"', ( canEdit ? '' : ' style="display:none"' ), '><a href="javascript:"',
            ' data-lang-add="', ( json.lang_add || 'Add description' ), '"',
            ' data-lang-edit="', ( json.lang_edit || 'Edit description' ), '">',
                ( json.content ? json.lang_edit : json.lang_add ),
            '</a>',
        '</div>'
    ].join('');
}

function br2nl( text ) {
    text = text || '';
    text = text.replace( /<br\s*\/?>/g, '\n' );
    return text;
}

function toggleDescription() {
    var $excerpt = elem.find('.joms-js--btn-desc-excerpt'),
        $fulltext = elem.find('.joms-js--btn-desc-fulltext'),
        $button = elem.find('.joms-js--btn-desc-toggle');

    if ( $fulltext.is(':visible') ) {
        $fulltext.hide();
        $excerpt.show();
        $button.html( window.joms_lang.COM_COMMUNITY_SHOW_MORE );
    } else {
        $excerpt.hide();
        $fulltext.show();
        $button.html( window.joms_lang.COM_COMMUNITY_SHOW_LESS );
    }
}

function editDescription() {
    elem.find('.joms-js--btn-desc-content').hide();
    elem.find('.joms-js--btn-desc-edit').hide();
    elem.find('.joms-js--btn-desc-editor').show();
}

function cancelDescription() {
    elem.find('.joms-js--btn-desc-editor').hide();
    elem.find('.joms-js--btn-desc-content').show();
    elem.find('.joms-js--btn-desc-edit').show();
}

function saveDescription() {
    var content  = elem.find('.joms-js--btn-desc-content'),
        editor   = elem.find('.joms-js--btn-desc-editor'),
        button   = elem.find('.joms-js--btn-desc-edit'),
        textarea = editor.find('textarea'),
        value    = $.trim( textarea.val() );

    joms.ajax({
        func: 'videos,ajaxSaveDescription',
        data: [ id, value ],
        callback: function( json ) {
            var a = button.find('a'),
                $cexcerpt, $cfulltext, $cbutton;

            if ( json.error ) {
                window.alert( json.error );
                return;
            }

            if ( json.success ) {

                $cexcerpt = content.find('.joms-js--btn-desc-excerpt');
                $cfulltext = content.find('.joms-js--btn-desc-fulltext');
                $cbutton = content.find('.joms-js--btn-desc-toggle');

                // Update content.
                if ( !json.caption || json.caption === json.excerpt ) {
                    $cexcerpt.hide();
                    $cbutton.hide();
                    $cfulltext.html( json.caption ).show();
                } else {
                    $cexcerpt.html( json.excerpt ).show();
                    $cbutton.html( window.joms_lang.COM_COMMUNITY_SHOW_MORE ).show();
                    $cfulltext.html( json.caption ).hide();
                }

                editor.hide();
                content.show();

                a.html( a.data( 'lang-' + ( value ? 'edit' : 'add' ) ) );
                button.show();
            }
        }
    });
}

// Exports.
return joms._.debounce(function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
}, 200 );

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.video || (joms.popup.video = {});
    joms.popup.video.add = factory( root, $ );

    define('popups/video.add',[ 'utils/loadlib', 'utils/popup' ], function() {
        return joms.popup.video.add;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, uploader, uploaderButton, contextid, context;

function render( _popup, _contextid, _context ) {
    var data;

    if ( elem ) elem.off();
    popup = _popup;
    context = _context || false;
    contextid = _contextid || false;

    data = [];
    if ( contextid ) {
        data.push( context || '' );
        data.push( contextid || '' );
    }

    joms.ajax({
        func: 'videos,ajaxAddVideo',
        data: data,
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;

            elem.on( 'submit', '.joms-js--form-link', link );
            elem.on( 'click', '.joms-js--select-file', upload );
            elem.on( 'submit', '.joms-js--form-upload', uploadStart );

            // Init uploader upon render.
            uploadInit();
        }
    });
}

function link( e ) {
    e.preventDefault();
    var form = $( e.currentTarget ),
        rTrim = /^\s+|\s+$/g,
        url = form.find('[name=videoLinkUrl]'),
        cat = form.find('[name=category_id]'),
        urlVal = url.val().trim( rTrim, '' ),
        catVal = +cat.val(),
        btnSubmit = form.find('[type=submit]'),
        langLinking = btnSubmit.data('lang-linking') || 'Linking***';

    url.siblings('[data-elem=form-warning]')[ urlVal ? 'hide' : 'show' ]();
    cat.parents('.joms-select--wrapper').siblings('[data-elem=form-warning]')[ catVal ? 'hide' : 'show' ]();

    if ( urlVal && catVal ) {
        form.removeAttr('onsubmit');
        btnSubmit.val(langLinking);
        btnSubmit.prop('disabled', true);
        elem.off( 'submit', '.joms-js--form-link' );
        setTimeout(function() {
           form.submit();
        }, 300 );
    }
}

function upload() {
    uploadInit(function() {
        uploaderButton.click();
    });
}

function uploadInit( callback ) {
    if ( typeof callback !== 'function' ) {
        callback = function() {};
    }

    if ( uploader ) {
        callback();
        return;
    }

    joms.util.loadLib( 'plupload', function () {
        var container, button;

        container = $('<div id="joms-js--videoupload-uploader" aria-hidden="true" style="width:1px; height:1px; overflow:hidden">').appendTo( document.body );
        button    = $('<button id="joms-js--videoupload-uploader-button">').appendTo( container );
        uploader  = new window.plupload.Uploader({
            url: 'index.php?option=com_community&view=videos&task=uploadvideo',
            filters: [{ title: 'Video files', extensions: '3g2,3gp,asf,asx,avi,flv,mov,mp4,mpg,rm,swf,vob,wmv,m4v' }],
            container: 'joms-js--videoupload-uploader',
            browse_button: 'joms-js--videoupload-uploader-button',
            runtimes: 'html5,html4',
            multi_selection: false
        });

        uploader.bind( 'FilesAdded', uploadAdded );
        uploader.bind( 'BeforeUpload', uploadBeforeUpload );
        uploader.bind( 'Error', uploadError );
        uploader.bind( 'UploadProgress', uploadProgress );
        uploader.bind( 'FileUploaded', uploadUploaded );
        uploader.bind( 'uploadComplete', uploadComplete );
        uploader.init();

        uploaderButton = container.find('input[type=file]');
        callback();
    });
}

function uploadAdded( up, files ) {
    if ( !(files && files.length) )
        return;

    var span = elem.find('.joms-js--select-file'),
        file = files[0],
        name = '<span>' + file.name + '</span>',
        size = file.size || 0,
        unit = 'Bytes';

    for ( var units = [ 'KB', 'MB', 'GB' ]; size >= 1000 && units.length; ) {
        unit = units.shift();
        size = Math.ceil( size / 1000 );
    }

    if ( size )
        name += ' <span>(' + size + ' ' + unit + ')</span>';

    span.html( name );
}

function uploadStart( e ) {
    e.preventDefault();
    var form = $( e.currentTarget ),
        rTrim = /^\s+|\s+$/g,
        title = form.find('[name=title]'),
        cat = form.find('[name=category_id]'),
        titleVal = title.val().trim( rTrim, '' ),
        catVal = +cat.val(),
        bar;

    title.siblings('[data-elem=form-warning]')[ titleVal ? 'hide' : 'show' ]();
    cat.siblings('[data-elem=form-warning]')[ catVal ? 'hide' : 'show' ]();

    if ( !titleVal || !catVal ) {
        return false;
    }

    bar = form.find('.joms-progressbar__progress');
    bar.css({ width: 0 });

    uploader.refresh();
    uploader.start();
}

function uploadBeforeUpload() {
    var raw = elem.find('.joms-js--form-upload').serializeArray(),
        params = {},
        i;

    for ( i = 0; i < raw.length; i++ ) {
        params[ raw[i].name ] = raw[i].value;
    }

    // Attach parameters to uploader.
    uploader.settings.multipart_params = params;
}

function uploadError( up, error ) {
    var message = 'Undefined error.';
    if ( error && error.code && error.message ) {
        message = '(' + error.code + ') ' + error.message;
    }

    elem.find('.joms-js--select-file').html('&nbsp;');
    uploader.splice();
    uploader.refresh();
    window.alert( message );
}

function uploadProgress( up, file ) {
    var percent, form, bar;

    percent = Math.min( 100, Math.floor( file.loaded / file.size * 100 ) );
    form = elem.find('.joms-js--form-upload');
    bar = form.find( '.joms-progressbar__progress' );
    bar.stop().animate({ width: percent + '%' });
}

function uploadUploaded( up, file, resp ) {
    var json = {};

    try {
        json = JSON.parse( resp.response );
    } catch (e) {}

    if ( json.status !== 'success' ){
        window.alert( json.message || 'Undefined error.' );
        return;
    }

    setTimeout(function() {
        window.alert( json.processing_str );
        popup.close();
    }, 1000 );
}

function uploadComplete() {
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock joms-popup--videoupload">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        ( json.html ? json.html : '<div class="joms-popup__content joms-popup__content--single">' + json.error + '</div>' ),
        '</div>'
    ].join('');
}

// Exports.
return function( contextid, context ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, contextid, context );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.video || (joms.popup.video = {});
    joms.popup.video.edit = factory( root );

    define('popups/video.edit',[ 'utils/popup' ], function() {
        return joms.popup.video.edit;
    });

})( window, function( window ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'videos,ajaxEditVideo',
        data: [ id, window.location.href ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    elem.find('form').submit();
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock joms-popup--600">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1', ( json.error ? ' joms-popup__hide' : '' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', json.html, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnCancel, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnSave, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-js--step2', ( json.error ? '' : ' joms-popup__hide' ), '">',
        '<div data-ui-object="popup-step-2"', ( json.error ? '' : ' class="joms-popup__hide"' ), '>',
            '<div class="joms-popup__content joms-popup__content--single">', (json.error || ''), '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.video || (joms.popup.video = {});
    joms.popup.video.fetchThumbnail = factory( root, $ );

    define('popups/video.fetchthumbnail',[ 'utils/popup' ], function() {
        return joms.popup.video.fetchThumbnail;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'videos,ajaxFetchThumbnail',
        data: [ id, 'myvideos' ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            if ( json.success ) {
                popup.st.callbacks || (popup.st.callbacks = {});
                popup.st.callbacks.close = function() {
                    window.location.reload();
                };
            }

            popup.updateItemHTML();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content joms-popup__content--single">',
        ( json.message || json.error || '' ),
        ( json.thumbnail ? '<div style="padding-top:10px;"><img src="' + json.thumbnail + '" style="max-width:100%;"></div>' : '' ),
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.video || (joms.popup.video = {});
    joms.popup.video.linkToProfile = factory( root, $ );

    define('popups/video.linktoprofile',[ 'utils/popup' ], function() {
        return joms.popup.video.linkToProfile;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'profile,ajaxConfirmLinkProfileVideo',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    joms.ajax({
        func: 'profile,ajaxLinkProfileVideo',
        data: [ id ],
        callback: function( json ) {
            elem.find('.joms-js--step1').hide();
            elem.find('.joms-js--step2').show().children().html( json.error || json.message );

            if ( json.success ) {
                setTimeout(function() {
                    window.location.reload();
                }, 1000 );
            }
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1">',
            '<div class="joms-popup__content">', json.html, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnNo, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-popup__hide joms-js--step2">',
            '<div class="joms-popup__content joms-popup__content--single"></div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.video || (joms.popup.video = {});
    joms.popup.video.remove = factory( root );

    define('popups/video.remove',[ 'utils/popup' ], function() {
        return joms.popup.video.remove;
    });

})( window, function( window ) {

var popup, elem, id;

function render( _popup, _id, redirect ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'videos,ajaxConfirmRemoveVideo',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', { redirect: redirect ? 1 : 0 }, save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save( e ) {
    joms.ajax({
        func: 'videos,ajaxRemoveVideo',
        data: [ id, e.data.redirect ],
        callback: function( json ) {
            elem.find('.joms-js--step1').hide();
            elem.find('.joms-js--step2').show().children().html( json.error || json.message );

            if ( json.success ) {
                setTimeout(function() {
                    if ( json.redirect ) {
                        window.location = json.redirect;
                    } else {
                        window.location.reload();
                    }
                }, 1000 );
            }
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1">',
            '<div class="joms-popup__content">', json.html, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnNo, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-popup__hide joms-js--step2">',
            '<div class="joms-popup__content joms-popup__content--single"></div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id, redirect ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id, redirect );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.video || (joms.popup.video = {});
    joms.popup.video.report = factory( root );

    define('popups/video.report',[ 'utils/popup' ], function() {
        return joms.popup.video.report;
    });

})( window, function() {

var popup, elem, id, url;

function render( _popup, _id, _url ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;
    url = _url;

    joms.ajax({
        func: 'system,ajaxReport',
        data: [],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'change', 'select', changeText );
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function changeText( e ) {
    elem.find('textarea').val( e.target.value );
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    var rTrim = /^\s+|\s+$/g,
        message;

    message = elem.find('textarea').val();
    message = message.replace( rTrim, '' );

    if ( !message ) {
        elem.find('.joms-js--error').show();
        return;
    }

    elem.find('.joms-js--error').hide();

    joms.ajax({
        func: 'system,ajaxSendReport',
        data: [ 'videos,reportVideo', url || window.location.href, message, id ],
        callback: function( json ) {
            elem.find('.joms-js--step1').hide();
            elem.find('.joms-js--step2').show().children().html( json.error || json.message );
        }
    });
}


function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock joms-popup--500">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1', ( json.error ? ' joms-popup__hide' : '' ), '">',
            json.html,
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnCancel, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnSend, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-js--step2', ( json.error ? '' : ' joms-popup__hide' ), '">',
            '<div class="joms-popup__content joms-popup__content--single">', ( json.error || '' ), '</div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id, url ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id, url );
    });
};

});
(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.video || (joms.popup.video = {});
    joms.popup.video.addFeatured = factory( root, $ );

    define('popups/video.addfeatured',[ 'utils/popup' ], function() {
        return joms.popup.video.addFeatured;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'videos,ajaxAddFeatured',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            if ( json.success ) {
                popup.st.callbacks || (popup.st.callbacks = {});
                popup.st.callbacks.close = function() {
                    window.location.reload();
                };
            }

            popup.updateItemHTML();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.html || json.error || '' ), '</div>',
        '<div class="joms-popup__action">',
        '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.video || (joms.popup.video = {});
    joms.popup.video.removeFeatured = factory( root, $ );

    define('popups/video.removefeatured',[ 'utils/popup' ], function() {
        return joms.popup.video.removeFeatured;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, id;

function render( _popup, _id ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;

    joms.ajax({
        func: 'videos,ajaxRemoveFeatured',
        data: [ id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            if ( json.success ) {
                popup.st.callbacks || (popup.st.callbacks = {});
                popup.st.callbacks.close = function() {
                    window.location.reload();
                };
            }

            popup.updateItemHTML();
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-popup__content joms-popup__content--single">', ( json.html || json.error || '' ), '</div>',
        '<div class="joms-popup__action">',
        '<button class="joms-button--neutral joms-button--small joms-js--button-close">', window.joms_lang.COM_COMMUNITY_CLOSE_BUTTON, '</button>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id );
    });
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.video || (joms.popup.video = {});
    joms.popup.video.removeLinkFromProfile = factory( root, $ );

    define('popups/video.removelinkfromprofile',[ 'utils/popup' ], function() {
        return joms.popup.video.removeLinkFromProfile;
    });

})( window, joms.jQuery, function( window ) {

var popup, elem, id, userid;

function render( _popup, _id, _userid ) {
    if ( elem ) elem.off();
    popup = _popup;
    id = _id;
    userid = _userid;

    joms.ajax({
        func: 'profile,ajaxRemoveConfirmLinkProfileVideo',
        data: [ userid, id ],
        callback: function( json ) {
            popup.items[0] = {
                type: 'inline',
                src: buildHtml( json )
            };

            popup.updateItemHTML();

            elem = popup.contentContainer;
            elem.on( 'click', '.joms-js--button-cancel', cancel );
            elem.on( 'click', '.joms-js--button-save', save );
        }
    });
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    joms.ajax({
        func: 'profile,ajaxRemoveLinkProfileVideo',
        data: [ userid, id ],
        callback: function( json ) {
            elem.find('.joms-js--step1').hide();
            elem.find('.joms-js--step2').show().children().html( json.error || json.message );

            if ( json.success ) {
                setTimeout(function() {
                    window.location.reload();
                }, 1000 );
            }
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1">',
            '<div class="joms-popup__content">', json.html, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnNo, '</button> &nbsp;',
            '<button class="joms-button--primary joms-button--small joms-js--button-save">', json.btnYes, '</button>',
            '</div>',
        '</div>',
        '<div class="joms-popup__hide joms-js--step2">',
            '<div class="joms-popup__content joms-popup__content--single"></div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( id, user ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, id, user );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.video = factory( root, joms.popup.video || {});

    define('popups/video',[
        'popups/video.open',
        'popups/video.add',
        'popups/video.edit',
        'popups/video.fetchthumbnail',
        'popups/video.linktoprofile',
        'popups/video.remove',
        'popups/video.report',
        'popups/video.addfeatured',
        'popups/video.removefeatured',
        'popups/video.removelinkfromprofile'
    ], function() {
        return joms.popup.video;
    });

})( window, function( window, sub ) {

// Exports.
return joms._.extend({}, sub );

});

(function( root, $, factory ) {

    joms.view || (joms.view = {});
    joms.view.cover = factory( root, $ );

    define('views/cover',[],function() {
        return joms.view.cover;
    });

})( window, joms.jQuery, function( window, $ ) {

var type, id, cover, img, hammertime, repositioning;

function reposition( _type, _id ) {
    var top, maxHeight;

    type  = _type;
    id    = _id;
    cover = $('.joms-focus__cover');
    img   = cover.children('.joms-js--cover-image').children('img');

    if ( !img ) return;

    cover.css('cursor', 'move');
    cover.children('.joms-focus__header').hide();
    cover.children('.joms-focus__actions--reposition')
        .on( 'click', 'input', repositionAction )
        .show();

    img.data( 'top', img.position().top );

    // set reposition flag
    repositioning = true;

    hammertime = new joms.Hammer( img[0] );
    hammertime.on( 'dragstart dragup dragdown dragend', function( e ) {
        var newTop;
        if ( e.type === 'dragstart' ) {
            top = img.position().top;
            maxHeight = cover.height() - img.height();
        } else if ( e.type !== 'dragend' ) {
            newTop = Math.min( 0, top + e.gesture.deltaY );
            newTop = Math.max( maxHeight, newTop );
            img.css({ top: newTop });
        }
    });
}

function repositionAction( e ) {
    var elem;
    elem = $( e.target );
    cover.children('.joms-focus__actions--reposition').off( 'click', 'input' );
    elem.data('ui-object') === 'button-save' ? repositionSave() : repositionCancel();
}

function repositionSave() {
    var top;

    top = pixelToPercent( img.position().top, cover.height() );
    repositionReset();

    joms.ajax({
        func: 'photos,ajaxSetPhotoPhosition',
        data: [ type, id, top ]
    });
}

function repositionCancel() {
    img.css({ top: img.data('top') });
    repositionReset();
}

function repositionReset() {
    cover.css('cursor', '');
    cover.children('.joms-focus__actions--reposition').hide();
    cover.children('.joms-focus__header').show();
    cover = null;
    img = null;
    hammertime = null;
    repositioning = null;
}

function pixelToPercent( imgTop, coverHeight ) {
    var percent;
    percent = imgTop * 100 / coverHeight;
    percent = Math.round( percent * 10000 ) / 10000;
    return percent + '%';
}

function click( albumId, photoId ) {
    if ( !repositioning ) {
        joms.api.photoOpen( albumId, photoId );
    }
}

// Exports.
return {
    reposition: reposition,
    click: click
};

});

(function( root, $, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.page || (joms.popup.page = {});
    joms.popup.page.share = factory( root, $ );

    define('popups/page.share',[ 'utils/popup' ], function() {
        return joms.popup.page.share;
    });

})( window, joms.jQuery, function( window, $ ) {

var popup, elem, url;

function render( _popup, _url ) {
    var title, description, image;

    if ( elem ) elem.off();
    popup = _popup;
    url = _url;

    $.ajax({
        url: url,
        success: function( response ) {
            title = response.match(/<meta property="og:title" content="([^"]+)"/i) || false;
            title = title && title[1];
            description = response.match(/<meta property="og:description" content="([^"]+)"/i) || false;
            description = description && description[1];
            image = response.match(/<meta property="og:image" content="([^"]+)"/i) || false;
            image = image && image[1];
        },
        complete: function() {
            var params = [ url, title || '', description || '', image || '' ];
            joms.ajax({
                func: 'bookmarks,ajaxShowBookmarks',
                data: params,
                callback: function( json ) {
                    popup.items[0] = {
                        type: 'inline',
                        src: buildHtml( json )
                    };

                    popup.updateItemHTML();

                    elem = popup.contentContainer;
                    elem.on( 'click', '.joms-bookmarks a', openPopup );
                    elem.on( 'click', '.joms-js--button-cancel', cancel );
                    elem.on( 'click', '.joms-js--button-save', save );
                }
            });
        }
    });
}

function openPopup( e ) {
    var $a, url, title;

    e.preventDefault();
    e.stopPropagation();

    $a = $( this );
    url = $a.attr('href');
    title = $a.text();

    elem.off();
    popup.close();
    window.open( url, title, 'top=150, left=150, width=650, height=330, scrollbars=yes' );
}

function cancel() {
    elem.off();
    popup.close();
}

function save() {
    var $form = elem.find('form'),
        email = $form.find('[name=bookmarks-email]').val(),
        message = $form.find('[name=bookmarks-message]').val();

    joms.ajax({
        func: 'bookmarks,ajaxEmailPage',
        data: [ url, email, message ],
        callback: function( json ) {
            elem.find('.joms-js--step1').hide();
            elem.find('.joms-js--step2').show().children().html( json.error || json.message );
        }
    });
}

function buildHtml( json ) {
    json || (json = {});

    return [
        '<div class="joms-popup joms-popup--whiteblock joms-popup--500">',
        '<div class="joms-popup__title"><button class="mfp-close" type="button" title="Close (Esc)">×</button>', json.title, '</div>',
        '<div class="joms-js--step1">',
            '<div class="joms-popup__content">', json.html, '</div>',
            '<div class="joms-popup__action">',
            '<button class="joms-button--neutral joms-button--small joms-left joms-js--button-cancel">', json.btnCancel, '</button>',
            ( json.viaEmail ? ' &nbsp;<button class="joms-button--primary joms-button--small joms-js--button-save">' + json.btnShare + '</button>' : '' ),
            '</div>',
        '</div>',
        '<div class="joms-popup__hide joms-js--step2">',
            '<div class="joms-popup__content joms-popup__content--single"></div>',
        '</div>',
        '</div>'
    ].join('');
}

// Exports.
return function( url ) {
    joms.util.popup.prepare(function( mfp ) {
        render( mfp, url );
    });
};

});

(function( root, factory ) {

    joms.popup || (joms.popup = {});
    joms.popup.page = factory( root, joms.popup.page || {});

    define('popups/page',[ 'popups/page.share' ], function() {
        return joms.popup.page;
    });

})( window, function( window, sub ) {

// Exports.
return joms._.extend({}, sub );

});

(function( root, $, factory ) {

    joms.view || (joms.view = {});
    joms.view.page = factory( root, $ );

    define('views/page',[ 'utils/hovercard', 'popups/page' ], function() {
        return joms.view.page;
    });

})( window, joms.jQuery, function( window, $ ) {

function initialize() {
    // joms.util.hovercard.initialize();
}

function like( type, id ) {
    joms.ajax({
        func: 'system,ajaxLike',
        data: [ type, id ],
        callback: function( json ) {
            if ( json.success ) {
                update( 'like', type, id, json.likeCount );
            }
        }
    });
}

function unlike( type, id ) {
    joms.ajax({
        func: 'system,ajaxUnlike',
        data: [ type, id ],
        callback: function( json ) {
            if ( json.success ) {
                update( 'unlike', type, id, json.likeCount );
            }
        }
    });
}

function share( url ) {
    joms.popup.page.share( url );
}

function update( action, type, id, count ) {
    var elem;

    elem = $( '.joms-js--like-' + type + '-' + id );
    elem.each(function() {
        var tagName = this.tagName.toLowerCase(),
            elem = $( this );

        if ( tagName === 'a' ) {
            if ( elem.hasClass('joms-popup__btn-like') ) {
                updatePopupButton( elem, action, type, id, count );
            } else {
                updateFocusButton( elem, action, type, id, count );
            }
        } else if ( tagName === 'button' ) {
            if ( elem.hasClass('joms-popup__btn-like') ) {
                updatePopupButton( elem, action, type, id, count );
            } else {
                updateButton( elem, action, type, id, count );
            }
        }
    });
}

function updatePopupButton( elem, action, type, id, count ) {
    var icon = '<svg viewBox="0 0 16 16" class="joms-icon"><use xlink:href="#joms-icon-thumbs-up"></use></svg>',
        lang;

    if ( action === 'like' ) {
        elem.attr( 'onclick', 'joms.view.page.unlike("' + type + '", "' + id + '");' );
        elem.addClass('liked');
        lang = elem.data('lang-liked');
    } else if ( action === 'unlike' ) {
        elem.attr( 'onclick', 'joms.view.page.like("' + type + '", "' + id + '");' );
        elem.removeClass('liked');
        lang = elem.data('lang-like');
    }

    lang = lang || elem.data('lang');
    count = +count;
    if ( count > 0 ) {
        lang += ' (' + count + ')';
    }

    elem.html( icon + ' <span>' + lang + '</span>' );
}

function updateFocusButton( elem, action, type, id, count ) {
    var lang;

    elem.find('span').html( count );

    if ( action === 'like' ) {
        elem.attr( 'onclick', 'joms.view.page.unlike("' + type + '", "' + id + '");' );
        elem.addClass('liked');
        if ( lang = elem.data('lang-liked') ) {
            elem.find('.joms-js--lang').text( lang );
        }
    } else if ( action === 'unlike' ) {
        elem.attr( 'onclick', 'joms.view.page.like("' + type + '", "' + id + '");' );
        elem.removeClass('liked');
        if ( lang = elem.data('lang-like') ) {
            elem.find('.joms-js--lang').text( lang );
        }
    }
}

function updateButton( elem, action, type, id, count ) {
    var lang;

    if ( action === 'like' ) {
        elem.attr( 'onclick', 'joms.view.page.unlike("' + type + '", "' + id + '");' );
        elem.removeClass('joms-button--neutral');
        elem.addClass('joms-button--primary');
        lang = elem.data('lang-liked');
    } else if ( action === 'unlike' ) {
        elem.attr( 'onclick', 'joms.view.page.like("' + type + '", "' + id + '");' );
        elem.addClass('joms-button--neutral');
        elem.removeClass('joms-button--primary');
        lang = elem.data('lang-like');
    }

    lang = lang || elem.data('lang') || '';
    count = +count;
    if ( count > 0 ) {
        lang += ' (' + count + ')';
    }

    elem.html( lang );
}

// Exports.
return {
    initialize: initialize,
    like: like,
    unlike: unlike,
    share: share
};

});

(function( root, factory ) {

    joms.api = factory( root );

    define('api',[
        'functions/announcement',
        'functions/facebook',
        'functions/invitation',
        'utils/field',
        'popups/info',
        'popups/login',
        'popups/album',
        'popups/announcement',
        'popups/app',
        'popups/avatar',
        'popups/comment',
        'popups/cover',
        'popups/discussion',
        'popups/event',
        'popups/fbc',
        'popups/file',
        'popups/friend',
        'popups/group',
        'popups/inbox',
        'popups/location',
        'popups/notification',
        'popups/photo',
        'popups/pm',
        'popups/search',
        'popups/tnc',
        'popups/user',
        'popups/video',
        'views/cover',
        'views/page',
        'views/stream'
    ], function() {
        return joms.api;
    });

})( window, function() {

// Exports.
return {

    /** Login form. */
    login: function( json ) {
        joms.popup.login( json );
    },

    /** User. */
    userChangeVanityURL: function( id ) {
        joms.popup.user.changeVanityURL( id );
    },
    userAddFeatured: function( id ) {
        joms.popup.user.addFeatured( id );
    },
    userRemoveFeatured: function( id ) {
        joms.popup.user.removeFeatured( id );
    },
    userBan: function( id ) {
        joms.popup.user.ban( id );
    },
    userUnban: function( id ) {
        joms.popup.user.unban( id );
    },
    userBlock: function( id ) {
        joms.popup.user.block( id );
    },
    userUnblock: function( id ) {
        joms.popup.user.unblock( id );
    },
    userIgnore: function( id ) {
        joms.popup.user.ignore( id );
    },
    userUnignore: function( id ) {
        joms.popup.user.unignore( id );
    },
    userReport: function( id ) {
        joms.popup.user.report( id );
    },

    /** Avatar (profile picture). */
    avatarChange: function( type, id, e ) {
        joms.popup.avatar.change( type, id );
        if ( e ) {
            e.stopPropagation();
            e.preventDefault();
        }
    },
    avatarRemove: function( type, id ) {
        joms.popup.avatar.remove( type, id );
    },
    avatarRotate: function( type, id, direction, callback ) {
        joms.popup.avatar.rotate( type, id, direction, callback );
    },

    /** Profile cover. */
    coverChange: function( type, id ) {
        joms.popup.cover.change( type, id );
    },
    coverRemove: function( type, id ) {
        joms.popup.cover.remove( type, id );
    },
    coverReposition: function( type, id ) {
        joms.view.cover.reposition( type, id );
    },
    coverClick: function( albumId, photoId ) {
        joms.view.cover.click( albumId, photoId );
    },

    /** Events. */
    eventInvite: function( id, type ) {
        joms.popup.event.invite( id, type );
    },
    eventJoin: function( id ) {
        joms.popup.event.join( id );
    },
    eventLeave: function( id ) {
        joms.popup.event.leave( id );
    },
    eventResponse: function() {
        joms.popup.event.response.apply( this, arguments );
    },
    eventAddFeatured: function( id ) {
        joms.popup.event.addFeatured( id );
    },
    eventRemoveFeatured: function( id ) {
        joms.popup.event.removeFeatured( id );
    },
    eventReport: function( id ) {
        joms.popup.event.report( id );
    },
    eventDelete: function( id ) {
        joms.popup.event[ 'delete' ]( id );
    },
    eventRejectGuest: function( id, userId ) {
        joms.popup.event.rejectGuest( id, userId );
    },
    eventBanMember: function( id, userId ) {
        joms.popup.event.banMember( id, userId );
    },
    eventUnbanMember: function( id, userId ) {
        joms.popup.event.unbanMember( id, userId );
    },
    eventUnpublish: function( id ) {
        joms.popup.event.unpublish( id );
    },

    /** Friends. */
    friendAdd: function( id ) {
        joms.popup.friend.add( id );
    },
    friendAddCancel: function( id ) {
        joms.popup.friend.addCancel( id );
    },
    friendRemove: function( id ) {
        joms.popup.friend.remove( id );
    },
    friendResponse: function( id ) {
        joms.popup.friend.response( id );
    },
    friendApprove: function( id ) {
        joms.popup.friend.approve( id );
    },
    friendReject: function( id ) {
        joms.popup.friend.reject( id );
    },

    /** Groups. */
    groupInvite: function( id ) {
        joms.popup.group.invite( id, 1, 1 );
    },
    groupJoin: function( id ) {
        joms.popup.group.join( id );
    },
    groupLeave: function( id ) {
        joms.popup.group.leave( id );
    },
    groupAddFeatured: function( id ) {
        joms.popup.group.addFeatured( id );
    },
    groupRemoveFeatured: function( id ) {
        joms.popup.group.removeFeatured( id );
    },
    groupReport: function( id ) {
        joms.popup.group.report( id );
    },
    groupUnpublish: function( id ) {
        joms.popup.group.unpublish( id );
    },
    groupDelete: function( id ) {
        joms.popup.group[ 'delete' ]( id );
    },
    groupApprove: function( id, userId ) {
        joms.popup.group.approve( id, userId );
    },
    groupRemoveMember: function( id, userId ) {
        joms.popup.group.removeMember( id, userId );
    },
    groupBanMember: function( id, userId ) {
        joms.popup.group.banMember( id, userId );
    },
    groupUnbanMember: function( id, userId ) {
        joms.popup.group.unbanMember( id, userId );
    },

    /** Notifications. */
    notificationGeneral: function() {
        joms.view.toolbar.notificationGeneral();
    },
    notificationFriend: function() {
        joms.view.toolbar.notificationFriend();
    },
    notificationPm: function() {
        joms.view.toolbar.notificationPm();
    },

    /** Photos. */
    photoUpload: function( albumId, contextId, context ) {
        joms.popup.photo.upload( albumId, contextId, context );
    },
    photoOpen: function( albumId, id ) {
        joms.popup.photo.open( albumId, id );
    },
    photoRemove: function( id ) {
        joms.popup.photo.remove( id );
    },
    photoZoom: function( url ) {
        joms.popup.photo.zoom( url );
    },
    photoSetAvatar: function( id ) {
        joms.popup.photo.setAvatar( id );
    },
    photoSetCover: function( albumId, id ) {
        joms.popup.photo.setCover( albumId, id );
    },
    photoSetAlbum: function( id ) {
        joms.popup.photo.setAlbum( id );
    },
    photoReport: function( id, url ) {
        joms.popup.photo.report( id, url );
    },

    /** Photo albums. */
    albumRemove: function( id ) {
        joms.popup.album.remove( id );
    },
    albumAddFeatured: function( id ) {
        joms.popup.album.addFeatured( id );
    },
    albumRemoveFeatured: function( id ) {
        joms.popup.album.removeFeatured( id );
    },

    /** Private messages. */
    pmSend: function( id ) {
        joms.popup.pm.send( id );
    },

    /** Videos. */
    videoAdd: function( contextId, context ) {
        joms.popup.video.add( contextId, context );
    },
    videoOpen: function( id ) {
        joms.popup.video.open( id );
    },
    videoEdit: function( id ) {
        joms.popup.video.edit( id );
    },
    videoRemove: function( id, redirect ) {
        joms.popup.video.remove( id, redirect );
    },
    videoAddFeatured: function( id ) {
        joms.popup.video.addFeatured( id );
    },
    videoRemoveFeatured: function( id ) {
        joms.popup.video.removeFeatured( id );
    },
    videoLinkToProfile: function( id ) {
        joms.popup.video.linkToProfile( id );
    },
    videoRemoveLinkFromProfile: function( id, userId ) {
        joms.popup.video.removeLinkFromProfile( id, userId );
    },
    videoFetchThumbnail: function( id ) {
        joms.popup.video.fetchThumbnail( id );
    },
    videoReport: function( id, url ) {
        joms.popup.video.report( id, url );
    },

    locationView: function( id ) {
        joms.popup.location.view( id );
    },

    /** Stream. */
    streamLike: function( id ) {
        joms.view.stream.like( id );
    },
    streamUnlike: function( id ) {
        joms.view.stream.unlike( id );
    },
    streamEdit: function( id, el ) {
        joms.view.stream.edit( id, el );
    },
    streamEditLocation: function( id ) {
        joms.view.stream.editLocation( id );
    },
    streamRemove: function( id ) {
        joms.view.stream.remove( id );
    },
    streamRemoveLocation: function( id ) {
        joms.view.stream.removeLocation( id );
    },
    streamRemoveMood: function( id ) {
        joms.view.stream.removeMood( id );
    },
    streamRemoveTag: function( id ) {
        joms.view.stream.removeTag( id );
    },
    streamSelectPrivacy: function( id ) {
        joms.view.stream.selectPrivacy( id );
    },
    streamShare: function( id ) {
        joms.view.stream.share( id );
    },
    streamHide: function( id, userId ) {
        joms.view.stream.hide( id, userId );
    },
    streamShowLikes: function( id, target ) {
        joms.view.stream.showLikes( id, target );
    },
    streamShowComments: function( id, type ) {
        joms.view.stream.showComments( id, type );
    },
    streamShowOthers: function( id ) {
        joms.view.stream.showOthers( id );
    },
    streamReport: function( id ) {
        joms.view.stream.report( id );
    },
    streamToggleText: function( id ) {
        joms.view.stream.toggleText( id );
    },
    streamAddFeatured: function( id ) {
        joms.view.stream.addFeatured( id );
    },
    streamRemoveFeatured: function( id ) {
        joms.view.stream.removeFeatured( id );
    },

    /** Streams. */
    streamsLoadMore: function() {
        joms.view.streams.loadMore();
    },

    /** Comment system. */
    commentLike: function( id ) {
        joms.view.comment.like( id );
    },
    commentUnlike: function( id ) {
        joms.view.comment.unlike( id );
    },
    commentEdit: function( id, el, type ) {
        joms.view.comment.edit( id, el, type );
    },
    commentCancel: function( id ) {
        joms.view.comment.cancel( id );
    },
    commentRemove: function( id, type ) {
        joms.view.comment.remove( id, type );
    },
    commentRemoveTag: function( id, type ) {
        joms.view.comment.removeTag( id, type );
    },
    commentRemovePreview: function( id, type ) {
        joms.view.comment.removePreview( id, type );
    },
    commentRemoveThumbnail: function( id, type ) {
        joms.view.comment.removeThumbnail( id, type );
    },
    commentShowLikes: function( id ) {
        joms.popup.comment.showLikes( id );
    },
    commentToggleText: function( id ) {
        joms.view.comment.toggleText( id );
    },

    /** Application */
    appAbout: function( name ) {
        joms.popup.app.about( name );
    },
    appBrowse: function( pos ) {
        joms.popup.app.browse( pos );
    },
    appPrivacy: function( name ) {
        joms.popup.app.privacy( name );
    },
    appRemove: function( id ) {
        joms.popup.app.remove( id );
    },
    appSetting: function( id, name ) {
        joms.popup.app.setting( id, name );
    },

    /** Search. */
    searchSave: function( data ) {
        joms.popup.search.save( data );
    },

    /** Page */
    pageLike: function( type, id ) {
        joms.view.page.like( type, id );
    },
    pageUnlike: function( type, id ) {
        joms.view.page.unlike( type, id );
    },
    pageShare: function( url ) {
        joms.view.page.share( url );
    },

    /** Invitation */
    invitationAccept: function( type, id ) {
        joms.fn.invitation.accept( type, id );
    },
    invitationReject: function( type, id ) {
        joms.fn.invitation.reject( type, id );
    },

    /** File */
    fileUpload: function( type, id ) {
        joms.popup.file.upload( type, id );
    },
    fileList: function( type, id ) {
        joms.popup.file.list( type, id );
    },
    fileDownload: function( type, id, path ) {
        joms.popup.file.download( type, id, path );
    },
    fileRemove: function( type, id ) {
        joms.popup.file.remove( type, id );
    },
    fileUpdateHit: function( id, location ) {
        joms.popup.file.updateHit( id, location );
    },

    /** Discussion */
    discussionLock: function( groupId, id ) {
        joms.popup.discussion.lock( groupId, id );
    },
    discussionRemove: function( groupId, id ) {
        joms.popup.discussion.remove( groupId, id );
    },

    /** Announcement */
    announcementEdit: function( groupId, id ) {
        joms.fn.announcement.edit( groupId, id );
    },
    announcementEditCancel: function( groupId, id ) {
        joms.fn.announcement.editCancel( groupId, id );
    },
    announcementRemove: function( groupId, id ) {
        joms.popup.announcement.remove( groupId, id );
    },

    /** Inbox */
    inboxRemove: function( task, msgIds ) {
        joms.popup.inbox.remove( task, msgIds );
    },
    inboxSetRead: function( msgIds, error ) {
        joms.popup.inbox.setRead( msgIds, error );
    },
    inboxSetUnread: function( msgIds, error ) {
        joms.popup.inbox.setUnread( msgIds, error );
    },

    /** Terms of services */
    tnc: function() {
        joms.popup.tnc();
    },

    /** Facebook connect */
    fbcUpdate: function() {
        joms.popup.fbc.update();
    }

};

});

require([
    'core',
    'utils/crop',
    'utils/dropdown',
    'utils/hovercard',
    'utils/loadlib',
    'utils/location-autocomplete',
    'utils/popup',
    'utils/tab',
    'utils/tagging',
    'utils/validation',
    'utils/video',
    'utils/wysiwyg',
    'functions/tagging',
    'views/comment',
    'views/customize',
    'views/misc',
    'views/stream',
    'views/streams',
    'views/toolbar',
    'api'
], function() {

    joms.onStart(function() {
        joms.view.comment.start();
        joms.view.page.initialize();
        joms.view.stream.start();
        joms.view.streams.start();
        joms.view.toolbar.start();
        joms.view.customize.start();
        joms.view.misc.start();

        joms.util.dropdown.start();
        joms.util.tab.start();
        joms.util.validation.start();
        joms.util.wysiwyg.start();
        joms.util.hovercard.initialize();

        // Fetch all friends in context (group, event, or default) if user is logged-in.
        if ( +window.joms_my_id ) {
            joms.fn.tagging.fetchFriendsInContext();
        }

    });

    joms.jQuery(function() {
        joms.start();
    });
});

define("init", function(){});

require.config({
	deps: [ 'init' ]
});

define("bundle", function(){});

}());