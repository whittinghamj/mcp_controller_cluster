/* $Id$ $Revision$ */
/* vim:set shiftwidth=4 ts=8: */

/*************************************************************************
 * Copyright (c) 2011 AT&T Intellectual Property 
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html
 *
 * Contributors: See CVS logs. Details at http://www.graphviz.org/
 *************************************************************************/

/* FIXME - incomplete replacement for codegen */

#include "config.h"

#include <stdarg.h>
#include <stdlib.h>
#include <string.h>
#include <ctype.h>

#ifdef _WIN32
#include <io.h>
#include "compat.h"
#endif

#include "macros.h"
#include "const.h"

#include "gvdevice.h"
#include "gvplugin_render.h"
#include "gvplugin_device.h"
#include "agxbuf.h"
#include "utils.h"
#include "color.h"

/* Number of points to split splines into */
#define BEZIERSUBDIVISION 6

typedef enum { FORMAT_HPGL, } format_type;

static int Depth;

static void hpglptarray(GVJ_t *job, pointf * A, int n, int close)
{
    int i;
    point p;

    for (i = 0; i < n; i++) {
	PF2P(A[i],p);
        gvprintf(job, " %d %d", p.x, p.y);
    }
    if (close) {
	PF2P(A[0],p);
        gvprintf(job, " %d %d", p.x, p.y);
    }
    gvputs(job, "\n");
}

static char *hpgl_string(char *s)
{
    static char *buf = NULL;
    static int bufsize = 0;
    int pos = 0;
    char *p;
    unsigned char c;

    if (!buf) {
        bufsize = 64;
        buf = malloc(bufsize * sizeof(char));
    }

    p = buf;
    while ((c = *s++)) {
        if (pos > (bufsize - 8)) {
            bufsize *= 2;
            buf = realloc(buf, bufsize * sizeof(char));
            p = buf + pos;
        }
        if (isascii(c)) {
            if (c == '\\') {
                *p++ = '\\';
                pos++;
            }
            *p++ = c;
            pos++;
        } else {
            *p++ = '\\';
            sprintf(p, "%03o", c);
            p += 3;
            pos += 4;
        }
    }
    *p = '\0';
    return buf;
}

static int hpglColorResolve(int *new, int r, int g, int b)
{
#define maxColors 256
    static int top = 0;
    static short red[maxColors], green[maxColors], blue[maxColors];
    int c;
    int ct = -1;
    long rd, gd, bd, dist;
    long mindist = 3 * 255 * 255;       /* init to max poss dist */

    *new = 0;                   /* in case it is not a new color */
    for (c = 0; c < top; c++) {
        rd = (long) (red[c] - r);
        gd = (long) (green[c] - g);
        bd = (long) (blue[c] - b);
        dist = rd * rd + gd * gd + bd * bd;
        if (dist < mindist) {
            if (dist == 0)
                return c;       /* Return exact match color */
            mindist = dist;
            ct = c;
        }
    }
    /* no exact match.  We now know closest, but first try to allocate exact */
    if (top++ == maxColors)
        return ct;              /* Return closest available color */
    red[c] = r;
    green[c] = g;
    blue[c] = b;
    *new = 1;                   /* flag new color */
    return c;                   /* Return newly allocated color */
}

/* this table is in xfig color index order */
static char *hpglcolor[] = {
    "black", "blue", "green", "cyan", "red", "magenta", "yellow", "white", (char *) NULL
};

static void hpgl_resolve_color(GVJ_t *job, gvcolor_t * color)
{
    int object_code = 0;        /* always 0 for color */
    int i, new;

    switch (color->type) {
	case COLOR_STRING:
	    for (i = 0; hpglcolor[i]; i++) {
		if (streq(hpglcolor[i], color->u.string)) {
		    color->u.index = i;
		    break;
		}
	    }
	    break;
	case RGBA_BYTE:
	    i = 32 + hpglColorResolve(&new,
			color->u.rgba[0],
			color->u.rgba[1],
			color->u.rgba[2]);
	    if (new)
		gvprintf(job, "%d %d #%02x%02x%02x\n",
			object_code, i,
			color->u.rgba[0],
			color->u.rgba[1],
			color->u.rgba[2]);
	    color->u.index = i;
	    break;
	default:
	    assert(0);	/* internal error */
    }

    color->type = COLOR_INDEX;
}

static void hpgl_line_style(obj_state_t *obj, int *line_style, double *style_val)
{
    switch (obj->pen) {
	case PEN_DASHED: 
	    *line_style = 1;
	    *style_val = 10.;
	    break;
	case PEN_DOTTED:
	    *line_style = 2;
	    *style_val = 10.;
	    break;
	case PEN_SOLID:
	default:
	    *line_style = 0;
	    *style_val = 0.;
	    break;
    }
}

static void hpgl_comment(GVJ_t *job, char *str)
{
    gvprintf(job, "# %s\n", str);
}

static void hpgl_begin_graph(GVJ_t * job)
{
    obj_state_t *obj = job->obj;

    gvputs(job, "#FIG 3.2\n");
    gvprintf(job, "# Generated by %s version %s (%s)\n",
	job->common->info[0], job->common->info[1], job->common->info[2]);
    gvprintf(job, "# Title: %s\n", obj->u.g->name);
    gvprintf(job, "# Pages: %d\n", job->pagesArraySize.x * job->pagesArraySize.y);
    gvputs(job, "Portrait\n"); /* orientation */
    gvputs(job, "Center\n");   /* justification */
    gvputs(job, "Inches\n");   /* units */
    gvputs(job, "Letter\n");   /* papersize */
    gvputs(job, "100.00\n");   /* magnification % */
    gvputs(job, "Single\n");   /* multiple-page */
    gvputs(job, "-2\n");       /* transparent color (none) */
    gvputs(job, "1200");	     /* resolution */
    gvputs(job, " 2\n");       /* coordinate system (upper left) */
}

static void hpgl_end_graph(GVJ_t * job)
{
    gvputs(job, "# end of FIG file\n");
}

static void hpgl_begin_page(GVJ_t * job)
{
    Depth = 2;
}

static void hpgl_begin_node(GVJ_t * job)
{
    Depth = 1;
}

static void hpgl_end_node(GVJ_t * job)
{
    Depth = 2;
}

static void hpgl_begin_edge(GVJ_t * job)
{
    Depth = 0;
}

static void hpgl_end_edge(GVJ_t * job)
{
    Depth = 2;
}

static void hpgl_textpara(GVJ_t * job, pointf p, textpara_t * para)
{
    obj_state_t *obj = job->obj;

    int object_code = 4;        /* always 4 for text */
    int sub_type = 0;           /* text justification */
    int color = obj->pencolor.u.index;
    int depth = Depth;
    int pen_style = 0;          /* not used */
    int font = -1;		/* init to xfig's default font */
    double font_size = para->fontsize * job->zoom;
    double angle = job->rotation ? (M_PI / 2.0) : 0.0;
    int font_flags = 4;		/* PostScript font */
    double height = 0.0;
    double length = 0.0;

    if (para->postscript_alias) /* if it is a standard postscript font */
	font = para->postscript_alias->xfig_code; 

    switch (para->just) {
    case 'l':
        sub_type = 0;
        break;
    case 'r':
        sub_type = 2;
        break;
    default:
    case 'n':
        sub_type = 1;
        break;
    }

    gvprintf(job,
            "%d %d %d %d %d %d %.1f %.4f %d %.1f %.1f %d %d %s\\001\n",
            object_code, sub_type, color, depth, pen_style, font,
            font_size, angle, font_flags, height, length, ROUND(p.x), ROUND(p.y),
            hpgl_string(para->str));
}

static void hpgl_ellipse(GVJ_t * job, pointf * A, int filled)
{
    obj_state_t *obj = job->obj;

    int object_code = 1;        /* always 1 for ellipse */
    int sub_type = 1;           /* ellipse defined by radii */
    int line_style;		/* solid, dotted, dashed */
    int thickness = obj->penwidth;
    int pen_color = obj->pencolor.u.index;
    int fill_color = obj->fillcolor.u.index;
    int depth = Depth;
    int pen_style = 0;          /* not used */
    int area_fill = filled ? 20 : -1;
    double style_val;
    int direction = 0;
    double angle = 0.0;
    int center_x, center_y, radius_x, radius_y;
    int start_x, start_y, end_x, end_y;

    hpgl_line_style(obj, &line_style, &style_val);

    start_x = center_x = ROUND(A[0].x);
    start_y = center_y = ROUND(A[0].y);
    radius_x = ROUND(A[1].x - A[0].x);
    radius_y = ROUND(A[1].y - A[0].y);
    end_x = ROUND(A[1].x);
    end_y = ROUND(A[1].y);

    gvprintf(job,
            "%d %d %d %d %d %d %d %d %d %.3f %d %.4f %d %d %d %d %d %d %d %d\n",
            object_code, sub_type, line_style, thickness, pen_color,
            fill_color, depth, pen_style, area_fill, style_val, direction,
            angle, center_x, center_y, radius_x, radius_y, start_x,
            start_y, end_x, end_y);
}

static void hpgl_bezier(GVJ_t * job, pointf * A, int n, int arrow_at_start,
	      int arrow_at_end, int filled)
{
    obj_state_t *obj = job->obj;

    int object_code = 3;        /* always 3 for spline */
    int sub_type;
    int line_style;		/* solid, dotted, dashed */
    int thickness = obj->penwidth;
    int pen_color = obj->pencolor.u.index;
    int fill_color = obj->fillcolor.u.index;
    int depth = Depth;
    int pen_style = 0;          /* not used */
    int area_fill;
    double style_val;
    int cap_style = 0;
    int forward_arrow = 0;
    int backward_arrow = 0;
    int npoints = n;
    int i;

    pointf pf, V[4];
    point p;
    int j, step;
    int count = 0;
    int size;

    char *buffer;
    char *buf;
    buffer =
        malloc((npoints + 1) * (BEZIERSUBDIVISION +
                                1) * 20 * sizeof(char));
    buf = buffer;

    hpgl_line_style(obj, &line_style, &style_val);

    if (filled) {
        sub_type = 5;     /* closed X-spline */
        area_fill = 20;   /* fully saturated color */
        fill_color = job->obj->fillcolor.u.index;
    }
    else {
        sub_type = 4;     /* opened X-spline */
        area_fill = -1;
        fill_color = 0;
    }
    V[3].x = A[0].x;
    V[3].y = A[0].y;
    /* Write first point in line */
    count++;
    PF2P(A[0], p);
    size = sprintf(buf, " %d %d", p.x, p.y);
    buf += size;
    /* write subsequent points */
    for (i = 0; i + 3 < n; i += 3) {
        V[0] = V[3];
        for (j = 1; j <= 3; j++) {
            V[j].x = A[i + j].x;
            V[j].y = A[i + j].y;
        }
        for (step = 1; step <= BEZIERSUBDIVISION; step++) {
            count++;
            pf = Bezier (V, 3, (double) step / BEZIERSUBDIVISION, NULL, NULL);
	    PF2P(pf, p);
            size = sprintf(buf, " %d %d", p.x, p.y);
            buf += size;
        }
    }

    gvprintf(job, "%d %d %d %d %d %d %d %d %d %.1f %d %d %d %d\n",
            object_code,
            sub_type,
            line_style,
            thickness,
            pen_color,
            fill_color,
            depth,
            pen_style,
            area_fill,
            style_val, cap_style, forward_arrow, backward_arrow, count);

    gvprintf(job, " %s\n", buffer);      /* print points */
    free(buffer);
    for (i = 0; i < count; i++) {
        gvprintf(job, " %d", i % (count - 1) ? 1 : 0);   /* -1 on all */
    }
    gvputs(job, "\n");
}

static void hpgl_polygon(GVJ_t * job, pointf * A, int n, int filled)
{
    obj_state_t *obj = job->obj;

    int object_code = 2;        /* always 2 for polyline */
    int sub_type = 3;           /* always 3 for polygon */
    int line_style;		/* solid, dotted, dashed */
    int thickness = obj->penwidth;
    int pen_color = obj->pencolor.u.index;
    int fill_color = obj->fillcolor.u.index;
    int depth = Depth;
    int pen_style = 0;          /* not used */
    int area_fill = filled ? 20 : -1;
    double style_val;
    int join_style = 0;
    int cap_style = 0;
    int radius = 0;
    int forward_arrow = 0;
    int backward_arrow = 0;
    int npoints = n + 1;

    hpgl_line_style(obj, &line_style, &style_val);

    gvprintf(job,
            "%d %d %d %d %d %d %d %d %d %.1f %d %d %d %d %d %d\n",
            object_code, sub_type, line_style, thickness, pen_color,
            fill_color, depth, pen_style, area_fill, style_val, join_style,
            cap_style, radius, forward_arrow, backward_arrow, npoints);
    hpglptarray(job, A, n, 1);        /* closed shape */
}

static void hpgl_polyline(GVJ_t * job, pointf * A, int n)
{
    obj_state_t *obj = job->obj;

    int object_code = 2;        /* always 2 for polyline */
    int sub_type = 1;           /* always 1 for polyline */
    int line_style;		/* solid, dotted, dashed */
    int thickness = obj->penwidth;
    int pen_color = obj->pencolor.u.index;
    int fill_color = 0;
    int depth = Depth;
    int pen_style = 0;          /* not used */
    int area_fill = 0;
    double style_val;
    int join_style = 0;
    int cap_style = 0;
    int radius = 0;
    int forward_arrow = 0;
    int backward_arrow = 0;
    int npoints = n;

    hpgl_line_style(obj, &line_style, &style_val);

    gvprintf(job,
            "%d %d %d %d %d %d %d %d %d %.1f %d %d %d %d %d %d\n",
            object_code, sub_type, line_style, thickness, pen_color,
            fill_color, depth, pen_style, area_fill, style_val, join_style,
            cap_style, radius, forward_arrow, backward_arrow, npoints);
    hpglptarray(job, A, n, 0);        /* open shape */
}

gvrender_engine_t hpgl_engine = {
    0,				/* hpgl_begin_job */
    0,				/* hpgl_end_job */
    hpgl_begin_graph,
    hpgl_end_graph,
    0,				/* hpgl_begin_layer */
    0,				/* hpgl_end_layer */
    hpgl_begin_page,
    0,				/* hpgl_end_page */
    0,				/* hpgl_begin_cluster */
    0,				/* hpgl_end_cluster */
    0,				/* hpgl_begin_nodes */
    0,				/* hpgl_end_nodes */
    0,				/* hpgl_begin_edges */
    0,				/* hpgl_end_edges */
    hpgl_begin_node,
    hpgl_end_node,
    hpgl_begin_edge,
    hpgl_end_edge,
    0,				/* hpgl_begin_anchor */
    0,				/* hpgl_end_anchor */
    0,				/* hpgl_begin_label */
    0,				/* hpgl_end_label */
    hpgl_textpara,
    hpgl_resolve_color,
    hpgl_ellipse,
    hpgl_polygon,
    hpgl_bezier,
    hpgl_polyline,
    hpgl_comment,
    0,				/* hpgl_library_shape */
};

static gvrender_features_t render_features_hpgl = {
    0,                          /* flags */
    4.,                         /* default pad - graph units */
    NULL,                       /* knowncolors */
    0,                          /* sizeof knowncolors */
    HSVA_DOUBLE,                /* color_type */
};

static gvdevice_features_t device_features_hpgl = {
    0,                          /* flags */
    {0.,0.},                    /* default margin - points */
    {0.,0.},                    /* default page width, height - points */
    {72.,72.},                  /* default dpi */
};

gvplugin_installed_t gvrender_hpgl_types[] = {
    {FORMAT_HPGL, "hpgl", -1, &hpgl_engine, &render_features_hpgl},
    {0, NULL, 0, NULL, NULL}
};

gvplugin_installed_t gvdevice_hpgl_types[] = {
    {FORMAT_HPGL, "hpgl:hpgl", -1, NULL, &device_features_hpgl},
    {0, NULL, 0, NULL, NULL}
};
