import subprocess, numpy as np
from PIL import Image, ImageDraw, ImageFont
W,H,FPS=1280,720,24
IMG="/home/user/ruth-jackson-coach/savana-step-tours/img/"
CLIP="/root/.claude/uploads/0d80fb94-f9f7-5902-9dc2-592bf5edb0c1/7b92f90c-sunset-2.mp4"
OUT="/home/user/ruth-jackson-coach/savana-step-tours/video/safari-film.mp4"
SERIF=ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSerif-Bold.ttf",54)
SANS=ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",26)
XF=12; PH=102
shade=Image.new("L",(1,H)); 
for y in range(H): shade.putpixel((0,y),int(max(0,(y-H*0.45)/(H*0.55))**1.5*190))
shade=shade.resize((W,H))
def caption(frame,t,n,title,sub):
    # fade in over 12 frames after 6, fade out over last 12
    a=min(1,max(0,(t-6)/12))*min(1,max(0,(n-XF-t)/12)) if n>0 else 1
    if a<=0: return frame
    base=frame.convert("RGBA")
    base.paste((12,14,18,255),mask=shade.point(lambda v:int(v*a)))
    ov=Image.new("RGBA",(W,H),(0,0,0,0)); d=ImageDraw.Draw(ov)
    d.text((70,H-170),title,font=SERIF,fill=(255,255,255,int(255*a)))
    d.text((72,H-96),sub,font=SANS,fill=(233,180,76,int(255*a)))
    return Image.alpha_composite(base,ov).convert("RGB")
def photo_frames(name,y0,y1,z0,z1,title,sub,n=PH):
    im=Image.open(IMG+name+".jpg").convert("RGB"); sw,sh=im.size; out=[]
    for i in range(n):
        p=i/(n-1); z=z0+(z1-z0)*p; w=sw/z; h=w*9/16
        cy=(y0+(y1-y0)*p)*sh; top=min(max(0,cy-h/2),sh-h); x0=(sw-w)/2
        f=im.resize((W,H),Image.LANCZOS,box=(x0,top,x0+w,top+h))
        out.append(caption(f,i,n,title,sub))
    return out
# clip frames, vertical video over blurred copy
cmd=["ffmpeg","-v","error","-i",CLIP,"-an","-vf",
 f"fps={FPS},split[a][b];[a]scale={W}:{H}:force_original_aspect_ratio=increase,crop={W}:{H}:0:1450,boxblur=40:2[bg];[b]scale=-2:{H}[fg];[bg][fg]overlay=(W-w)/2:0",
 "-f","rawvideo","-pix_fmt","rgb24","-"]
raw=subprocess.run(cmd,capture_output=True,check=True).stdout
cl=[Image.fromarray(a) for a in np.frombuffer(raw,np.uint8).reshape(-1,H,W,3)][:120]
cl=[caption(f,i,len(cl),"Feel the experience","Savana Step Tours") for i,f in enumerate(cl)]
segs=[cl,
 photo_frames("lodge-deck",0.62,0.45,1.0,1.12,"Sleep under African skies","Camps with a view of the plains"),
 photo_frames("rocks-beach",0.65,0.5,1.0,1.12,"Wild shores and quiet coves","Time to slow down"),
 photo_frames("coral",0.35,0.6,1.0,1.12,"Colour beneath the waves","Add a coast stop to any trip"),
 photo_frames("nile",0.70,0.82,1.0,1.08,"Plan your safari","info@savanasteptours.com   |   +254 700 123 456")]
# crossfade
frames=list(segs[0])
for s in segs[1:]:
    for i in range(XF):
        a=(i+1)/(XF+1); frames[len(frames)-XF+i]=Image.blend(frames[len(frames)-XF+i],s[i],a)
    frames+=s[XF:]
print(len(frames),len(frames)/FPS,"s")
p=subprocess.Popen(["ffmpeg","-v","error","-y","-f","rawvideo","-pix_fmt","rgb24","-s",f"{W}x{H}","-r",str(FPS),"-i","-",
 "-c:v","libx264","-preset","slow","-crf","27","-pix_fmt","yuv420p","-movflags","+faststart",OUT],stdin=subprocess.PIPE)
for f in frames: p.stdin.write(np.asarray(f).tobytes())
p.stdin.close(); p.wait()
