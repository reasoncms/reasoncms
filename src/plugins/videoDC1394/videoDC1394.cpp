////////////////////////////////////////////////////////
//
// GEM - Graphics Environment for Multimedia
//
// zmoelnig@iem.kug.ac.at
//
// Implementation file
//
//    Copyright (c) 1997-1998 Mark Danks.
//    Copyright (c) G�nther Geiger.
//    Copyright (c) 2001-2002 IOhannes m zmoelnig. forum::f�r::uml�ute. IEM
//    For information on usage and redistribution, and for a DISCLAIMER OF ALL
//    WARRANTIES, see the file, "GEM.LICENSE.TERMS" in this distribution.
//
/////////////////////////////////////////////////////////
#ifdef HAVE_CONFIG_H
# include "config.h"
#endif

#include "videoDC1394.h"
using namespace gem;

#include "Gem/RTE.h"
#include "Base/GemException.h"

#ifdef HAVE_LIBDC1394
REGISTER_VIDEOFACTORY("dc1394", videoDC1394);
/////////////////////////////////////////////////////////
//
// videoDC1394
//
/////////////////////////////////////////////////////////
// Constructor
//
/////////////////////////////////////////////////////////
videoDC1394 :: videoDC1394() : video(),
                               m_dccamera(NULL),
                               m_dcframe(NULL),
                               m_dc(NULL)
{
  m_dc = dc1394_new(); /* Initialize libdc1394 */
  if(!m_dc) throw(GemException("unable to initialize DC1394"));

  m_channel=-1;

  m_frame.xsize=1600;
  m_frame.ysize=1200;
  m_frame.setCsizeByFormat(GL_RGBA);
  m_frame.allocate();

  provide("dc1394");
  provide("iidc");
}

/////////////////////////////////////////////////////////
// Destructor
//
/////////////////////////////////////////////////////////
videoDC1394 :: ~videoDC1394(){
  close();

  if(m_dccamera)dc1394_camera_free (m_dccamera);m_dccamera=NULL;
  if(m_dc)dc1394_free(m_dc);m_dc=NULL;
}
/////////////////////////////////////////////////////////
// render
//
/////////////////////////////////////////////////////////
bool videoDC1394 :: grabFrame()
{
  dc1394video_frame_t*frame, *colframe;
  dc1394error_t err=dc1394_capture_dequeue(m_dccamera, DC1394_CAPTURE_POLICY_POLL, &frame);/* Capture */
  if((DC1394_SUCCESS!=err)||(NULL==frame)) {
    usleep(10);
    return true;
  }

  /* do something with the frame */
  colframe=( dc1394video_frame_t*)calloc(1,sizeof(dc1394video_frame_t));
  colframe->color_coding=DC1394_COLOR_CODING_RGB8;
  dc1394_convert_frames(frame, colframe);

  m_frame.xsize=frame->size[0];
  m_frame.ysize=frame->size[1];
  m_frame.setCsizeByFormat(GL_RGBA);
  m_frame.fromRGB(colframe->image);

  lock();
  m_image.image.convertFrom(&m_frame, m_reqFormat); 
  m_image.newimage=true;
  m_image.image.upsidedown=true;
  unlock();

  free(colframe->image);
  free(colframe);

  /* Release the buffer */
  err=dc1394_capture_enqueue(m_dccamera, frame);
  if(DC1394_SUCCESS!=err) {
    return false;
  }

  return true;
}

/////////////////////////////////////////////////////////
// openDevice
//
/////////////////////////////////////////////////////////
bool videoDC1394 :: openDevice(){
  dc1394error_t err;
  dc1394camera_list_t *list=NULL;
  
  err=dc1394_camera_enumerate (m_dc, &list); /* Find cameras */
  if(DC1394_SUCCESS!=err) {
    error("videoDC1394: %s: failed to enumerate", dc1394_error_get_string(err));
    return false;
  }
  if (list->num < 1) {
    error("videoDC1394: no cameras found");
    dc1394_camera_free_list (list);
    return false;
  }
  unsigned int devicenum=0;
  if(m_devicenum>=0)devicenum=m_devicenum;
  else if (!m_devicename.empty()) {
    int i=0;
    for(i=0; i<list->num; i++) {
      // find camera based on its GUID
      char buf[64];
      snprintf(buf, 64, "%x", list->ids[devicenum].guid);
      std::string name=buf;
      if(name==m_devicename){
	devicenum=i;
	break;
      }
      snprintf(buf, 64, "%x:%d", 
	       list->ids[devicenum].guid,
	       list->ids[devicenum].unit);
      name=buf;
      if(name==m_devicename){
	devicenum=i;
	break;
      }
    }
  }

  if (devicenum < list->num) {
  /* Work with first camera */
    m_dccamera = dc1394_camera_new_unit(m_dc, 
					list->ids[devicenum].guid,
					list->ids[devicenum].unit);
  } else {
    m_dccamera=NULL;
    error("videoDC1394: only found %d cameras but requested #%d!", list->num, devicenum);
  }
  dc1394_camera_free_list (list);

  if(!m_dccamera) {
    error("videoDC1394: could not access camera!");
    return false;
  }

  verbose(1, "videoDC1394: using camera with GUID %x\n", m_dccamera->guid);


  /* check supported video modes */
  dc1394video_modes_t video_modes;
  dc1394video_mode_t  video_mode;
  dc1394color_coding_t coding;

  err=dc1394_video_get_supported_modes(m_dccamera,&video_modes);
  if(DC1394_SUCCESS!=err) {
    error("can't get video modes");
    closeDevice();
    return false;
  }
  int mode=m_channel;
  if(mode>=video_modes.num) {
    error("requested channel %d/%d out of bounds", mode, video_modes.num);
  }

  int i;
  for (i=video_modes.num-1;i>=0;i--) {
    unsigned int w=0, h=0;
    if(DC1394_SUCCESS==dc1394_get_image_size_from_video_mode(m_dccamera, video_modes.modes[i], &w, &h)) {
      verbose(1, "videomode[%02d/%d]=%dx%d", i, video_modes.num, w, h);
    } else verbose(1, "videomode %d refused dimen: %d", i, video_modes.modes[i]);

    dc1394_get_color_coding_from_video_mode(m_dccamera,video_modes.modes[i], &coding);
    dc1394bool_t iscolor=DC1394_FALSE;
    if(DC1394_SUCCESS==dc1394_is_color(coding, &iscolor)) {
      verbose(1, "videomode[%02d/%d] %d is%scolor", i, video_modes.num, coding, (iscolor?" ":" NOT "));
    }

    if(mode<0) {  // find a mode matching the user's needs
      if(m_width==w && m_height==h) {
	// what about color?
	mode=i;
	break;
      }
    }
  }

  if(mode<0) {
    // select highest res mode:
    for (i=video_modes.num-1;i>=0;i--) {
      if (!dc1394_is_video_mode_scalable(video_modes.modes[i])) {
	dc1394_get_color_coding_from_video_mode(m_dccamera,video_modes.modes[i], &coding);
	
	video_mode=video_modes.modes[i];
	break;
      }
    }
    if (i < 0) {
      error("Could not get a valid mode");
      closeDevice();
      return false;
    }
  } else {
    video_mode=video_modes.modes[mode];
  }

  dc1394speed_t speed=DC1394_ISO_SPEED_400;
  err=dc1394_video_set_iso_speed(m_dccamera, speed);
  if(DC1394_SUCCESS!=err) {
    dc1394_video_get_iso_speed(m_dccamera, &speed);
    verbose(1, "default to ISO speed 100*2^%d", speed);
  }

  // get highest framerate
  dc1394framerates_t framerates;
  dc1394framerate_t framerate;

  err=dc1394_video_get_supported_framerates(m_dccamera,video_mode,&framerates);
  if(DC1394_SUCCESS==err) {
    framerate=framerates.framerates[framerates.num-1];
    err=dc1394_video_set_framerate(m_dccamera, framerate);
    float fr=0;
    dc1394_framerate_as_float(framerate, &fr);
    verbose(1, "DC1394: set framerate to %g", fr);
  }

  err=dc1394_capture_setup(m_dccamera, 
                           4,  /* 4 DMA buffers */
                           DC1394_CAPTURE_FLAGS_DEFAULT);     /* Setup capture */
  if(DC1394_SUCCESS!=err) {
    error("videoDC1394: %s: failed to enumerate", dc1394_error_get_string(err));
    return false;
  }

  verbose(1, "DC1394: Successfully opened...");
  return true;
}
/////////////////////////////////////////////////////////
// closeDevice
//
/////////////////////////////////////////////////////////
void videoDC1394 :: closeDevice(void){
  if(m_dccamera) {
    dc1394error_t err=dc1394_capture_stop(m_dccamera);  /* Stop capture */
  }
}

/////////////////////////////////////////////////////////
// startTransfer
//
/////////////////////////////////////////////////////////
bool videoDC1394 :: startTransfer()
{
  /* Start transmission */
  dc1394error_t err=dc1394_video_set_transmission(m_dccamera, DC1394_ON); 
  if(DC1394_SUCCESS!=err) {
    return false;
  }

  return true;
}

/////////////////////////////////////////////////////////
// stopTransfer
//
/////////////////////////////////////////////////////////
bool videoDC1394 :: stopTransfer()
{
  /* Stop transmission */
  dc1394error_t err=dc1394_video_set_transmission(m_dccamera, DC1394_OFF); 
  if(DC1394_SUCCESS!=err) {
    error("unable to stop transmission");
  }
  return true;
}


bool videoDC1394 :: setColor(int format){
  if (format<=0)return false;
  m_reqFormat=format;
  return true;
}

#else
videoDC1394 :: videoDC1394() : video()
{}
videoDC1394 :: ~videoDC1394()
{}

#endif // HAVE_LIBDC1394
