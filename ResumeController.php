<?php

namespace App\Http\Controllers\Index;

use App\Model\Enclosure;
use App\Model\Expected;
use App\Model\Porject;
use App\Model\Release;
use App\Model\Resume;
use App\Model\ResumeReseale;
use App\Model\School;
use App\Model\User;
use App\Model\Works;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Germey\Geetest\CaptchaGeetest;
use App\Model\Education;

class ResumeController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests, CaptchaGeetest;

    /**  我的简历的首页
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function index()
    {
        //$sum是判断简历能给打多少分
        $sum = '';

        //查询出学历表所有数据
        $education = Education::selAll();
        $u_id = session('u_id'); //用户Id
        $user = User::selOne($u_id);
        if ($user['u_cid'] != 0) {
            return redirect('/');
        }

        //根据用户查询所有简历信息
        $res = Resume::selOne(['u_id' => $u_id]);
        if ($res) {
            if ($res['r_img']) {
                $sum += 5;
            }

            if ($res['r_desc']) {
                $sum += 5;
            }
            $sum += 15;
        }

        //根据简历Id查询出所有作品
        if ($works = Works::selAll(['r_id' => $res['r_id']])) {
            $sum += 20;
        };

        //根据简历Id查询出所有项目
        if ($porject = Porject::selAll(['r_id' => $res['r_id']])) {
            $sum += 20;
        };

        //根据简历id查询出期望的工作
        if ($expected = Expected::SelOne(['r_id' => $res['r_id']])) {
            $sum += 15;
        }

        //根据简历id查询出教育背景
        if ($school = School::selOne(['r_id' => $res['r_id']])) {
            $sum += 20;
        };
        session()->put('sum', $sum);
        //赋值到表单页面,传对应的值
        return view('index.resume.resume', [
            'education' => $education,
            'res' => $res,
            'school' => $school,
            'works' => $works,
            'porject' => $porject,
            'expected' => $expected,
            'sum' => $sum
        ]);
    }


    /** 添加简历的基本信息
     * @param Request $request
     * @return mixed
     */
    public function educationPro(Request $request)
    {

//        return json_encode($request->input());
        //自带表单验证

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'sex' => 'required',
            'highestEducation' => 'required',
            'email' => 'required',
            'phone' => 'required',
            'status' => 'required',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors());
        }


        //接收表单传值
        $data['r_name'] = $request->input('name');
        $data['r_sex'] = $request->input('sex');
        $highestEducation = $request->input('highestEducation');
        //判断学历
        $education = Education::selAll();

        //判断学历

        foreach ($education as $k => $v) {
            if ($highestEducation == $v['ed_name']) {
                $data['r_education'] = $v['ed_id'];
            }
        }
        $data['r_email'] = $request->input('email');
        $data['r_photo'] = $request->input('phone');
        $data['r_status'] = $request->input('status');

        $data['r_time'] = time();
        $data['u_id'] = $request->session()->get('u_id');

           $re=Resume::updateResume($data, ['u_id' => $data['u_id']]);
            if($re==1){
                $resume=Resume::selOne(['u_id' => $data['u_id']]);
                $resume['r_education']=$highestEducation;
                return json_encode($resume);
            }else{
                return 0;
            }

    }

    /** 上传头像
     * @param Request $request
     */
    public function educationUpload(Request $request)
    {
//        return $_FILES['headPic']['tmp_name'];die;
        //获取用户id
        $u_id = $request->session()->get('u_id');

        $resume = Resume::selOne(['u_id' => $u_id]);

        //拼接图片地址
        $data['r_img'] = 'uploads/' . session('u_email') . rand(0, 999) . '.jpg';
        move_uploaded_file($_FILES['headPic']['tmp_name'], $data['r_img']);
        //判断图片是否存在,进行删除替换
        if (file_exists($resume['r_img'])) {
            unlink($resume['r_img']);
        };
        $res = Resume::updateResume($data, ['u_id' => $u_id]);

        if ($res) {
            return $data['r_img'];
        } else {
            return $data['r_img'];
        }

    }

    /**添加(修改)期望工作
     * @param Request $request
     * @return mixed
     */
    public function expectedAdd(Request $request)
    {
        //自带验证
        $validator = Validator::make($request->all(), [
            'positionName' => 'required',
            'salaryMin' => 'required',
            'salaryMax' => 'required',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors());
        }
        //接收表单的值
        $data['ex_name'] = $request->input('positionName');
        $data['re_salarymin'] = $request->input('salaryMin');
        $data['re_salarymax'] = $request->input('salaryMax');
        $data['r_id'] = $request->input('id');
        $res = Expected::SelOne(['r_id' => $data['r_id']]);
        if ($res) {
            $expected=Expected::expectedUp(['r_id' => $data['r_id']], $data);
            if($expected){
                return json_encode(Expected::selOne(['r_id'=>$data['r_id']]));
            }else{
                return 0;
            }
        } else {
            Expected::expectedAdd($data);
            return json_encode(Expected::selOne(['r_id'=>$data['r_id']]));

        }
    }

    /**删除期望工作
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function expectedDel(Request $request)
    {
        $id=$request->input('expectedId');
        return Expected::expectedDel(['r_id' => $id]);


    }


    /** * 添加项目
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function porjectAdd(Request $request)
    {
        //表单自带验证
        $validator = Validator::make($request->all(), [
            'projectName' => 'required',
            'positionName' => 'required',
            'startYear' => 'required',
            'startMonth' => 'required',
            'endYear' => 'required',
            'projectid' => 'required',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors());
        }

        //接收表单的值
        $data['p_name'] = $request->input('projectName');//项目名称
        $data['p_duties'] = $request->input('positionName');//担任职务
        $data['p_start_time'] = strtotime($request->input('startYear') . '-' . $request->input('startMonth'));//项目开始年月
        $data['p_end_time'] = ($request->input('endYear') == '至今') ? strtotime(date('Y-m',time())) : strtotime($request->input('endYear') . '-' . $request->input('endMonth'));//项目结束年月
        $data['p_desc'] = $request->input('projectRemark');//项目描述
        $data['r_id'] = $request->input('projectid');//对应简历的Id


        $res = Porject::addProject($data);

        if ($res) {
            $res=Porject::selAll(['r_id'=>$data['r_id']]);
                foreach ($res as $k=>$v) {
                    $res[$k]['p_start_time']=date('Y.m',$v['p_start_time']);
                    $res[$k]['p_end_time']=date('Y.m',$v['p_end_time']);
                }
            return json_encode($res);



        } else {

            return 0;
        }
    }

//    public function porjectSel(Request $request){
//         $pid= $request->input('pid');
//
//        return json_encode(Porject::selOne(['p_id'=>$pid]));
//
//
//    }


    /**   * 删除项目
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function porjectDel(Request $request)
    {
        $id=$request->input('porjectId');
        $re = Porject::delPorject(['p_id' => $id]);
        if ($re == 1) {
            $res = Resume::selOne(['u_id' => session('u_id')]);
            if ($porject = Porject::selAll(['r_id' => $res['r_id']])) {
               return  2;
            }else{
                return 1;
            }
        } else {
            return 0;
        }
    }



    /**  教育背景的添加
     * @param Request $request
     * @return $this
     */
    public function schoolPro(Request $request)
    {
        //自带表单的验证
        $validator = Validator::make($request->all(), [
            'schoolName' => 'required',
            'education' => 'required',
            'professional' => 'required',
            'startYear' => 'required',
            'endYear' => 'required',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors());
        }

        $sc_data=$request->input();
        //接收表单的值
        $data['s_name'] = $request->input('schoolName');
        $ed_name = $request->input('education');
        $education = Education::selAll();

        //判断学历

        foreach ($education as $k => $v) {
            if ($ed_name == $v['ed_name']) {
                $data['ed_id'] = $v['ed_id'];
            }
        }

        $data['s_major'] = $request->input('professional');
        $data['s_start_time'] = strtotime($request->input('startYear') . '-01-01');
        $data['s_end_time'] = strtotime($request->input('endYear') . '-01-01');
        $res = Resume::selOne(['u_id' => session('u_id')]);
        $data['r_id'] = $res['r_id'];

        $school = School::selOne(['r_id' => $res['r_id']]);

        //判断是修改还是添加
        if ($school) {
            $res = School::updateSchool($data, ['r_id' => $res['r_id']]);
            if ($res) {
                return json_encode($sc_data);
            } else {
                return 0;
            }
        } else {
            $res = School::addSchool($data);
            if ($res) {
                return json_encode($sc_data);
            } else {
                return 0;
            }
        }
    }

    /**删除教育背景
     * @param Request $request
     * @return mixed
     */
    public function schoolDel(Request $request){

        $id=$request->input('schoolId');
        return  School::delSchool(['r_id'=>$id]);

    }



    /** 添加简历中的个人描述
     * @param Request $request
     * @return $this
     */
    public function educationDesc(Request $request)
    {


        //接收表单的值
        $data['r_desc'] = $request->input('myRemark');
        $data['r_time'] = time();
        $r_id = $request->input('id');
        $res = Resume::updateResume($data, ['r_id' => $r_id]);

        //判断是否修改成功
        if ($res) {
            return json_encode($data);
        } else {
            return 0;
        }
    }


    /**   *添加作品
     * @param Request $request
     * @return array|string
     */
    public function worksAdd(Request $request)
    {
        //表单自带验证

        $validator = Validator::make($request->all(), [
            'url' => 'required',
            'workName' => 'required',
            'wsid' => 'required',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator->errors());
        }

        //接收表单的值
        $data['w_url'] = $request->input('url');
        $data['w_desc'] = $request->input('workName');
        $data['r_id'] = $request->input('wsid');
        $res = Works::addWorks($data);
        //判断是否添加成功
        if ($res) {
            return json_encode(Works::selAll(['r_id'=>$data['r_id']]));
        } else {
            return 0;
        }
    }

    /**     删除个人的作品
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function worksDel(Request $request)
    {
        $id=$request->input('workId');
        $re = Works::delWorks(['w_id' => $id]);
        if ($re == 1) {
            $res = Resume::selOne(['u_id' => session('u_id')]);
            if ($works = Works::selAll(['r_id' => $res['r_id']])) {
                return 2;
            }else{
                return 1;
            }
        } else {
            return 0;
        }
    }




//    public function enclosureAdd(Request $request){
//
//
//         $id=$request->input('userId');
//         $enclosure=Enclosure::selOne(['r_id'=>$id]);
//        if(count($enclosure)>=3){
//            return 3;
//        }else{
//            $newR=explode('.',$_FILES['newResume']['name']);
//
//            $type=array_pop($newR);
//
//            $data['e_path']= 'uploads/' . session('u_email') . rand(0, 999) . '.'.$type;
//            $data['e_name']=$_FILES['newResume']['name'];
//            $data['r_id']=$id;
//            move_uploaded_file($_FILES['newResume']['tmp_name'], $data['e_name']);
//            $res=Enclosure::enclosureAdd($data);
//                if ($res) {
//
//                    return json_encode($data);
//                } else {
//
//                    return 0;
//                }
//        }
//    }



    /**投递简历
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function remusePro($id)
    {
        $resume = Resume::selOne(['u_id' => session('u_id')]);

        $re_id=ResumeReseale::selOne(['re_id'=>$id,'r_id'=>$resume['r_id']]);
         if($re_id){
             return "<script>alert('你已此职位投递过简历');</script>".redirect('remuseShow');
         }

        $release_id=Release::releaseSel(['re_id'=>$id]);
//        print_r($release_id);die;
        if (!$release_id) {

            return "<script>alert('此职位不存在');location.href='/'</script>";
        }


        $t = time();
        $start = mktime(0,0,0,date("m",$t),date("d",$t),date("Y",$t));
        $end = mktime(23,59,59,date("m",$t),date("d",$t),date("Y",$t));

        $rereAlls=ResumeReseale::rereAll(['r_id'=>$resume['r_id']],$start,$end);

        $count=count($rereAlls);
        if($count>=10){
            return "<script>alert('今天已投递10次简历');location.href='/'</script>";
        }

        $education = Education::selAll();

        if($resume['r_sex']==0){
            $resume['r_sex']='男';
        }else{
            $resume['r_sex']='女';
        }
//        作品
        $resume['works']=Works::selAll(['r_id'=>$resume['r_id']]);

        //期望工作
        $resume['expected']=Expected::selOne(['r_id'=>$resume['r_id']]);


        //项目经验
        $resume['porject']=Porject::selAll(['r_id'=>$resume['r_id']]);
//        教育背景

        $resume['school']=School::selOne(['r_id'=>$resume['r_id']]);
        foreach ($education as $k => $v) {
            if ($resume['school']['ed_id'] == $v['ed_id']) {
                $resume['school']['ed_id'] = $v['ed_name'];
            }
        }

            //判断学历
        foreach ($education as $k => $v) {
            if ($resume['r_education'] == $v['ed_id']) {
                $resume['r_education'] = $v['ed_name'];
            }
        }


        $data['r_id'] = $resume['r_id'];//简历的id
        $data['re_id'] = $id; //职位id
        $data['delivery_time'] = time();
        unset($resume['r_id']);
        $data['rere_content']=json_encode($resume);
        $res = ResumeReseale::reAdd($data);
        if ($res == 1) {
            //发送消息
            $u_id=User::userSel(['u_cid'=>$release_id['c_id']]); //投递简历的那家公司的id
            $content="您好，您公司招聘的职位，已有人投递简历。<a href='".env('APP_HOST')."nndetermined'>查看</a>";
            MessageController::sendMessage($u_id,$content,1);
            return redirect('remuseShow');
        } else {
            return "<script>alert('投递失败');history.go(-1)</script>";
        }
    }


    /**已投递简历状态
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function remuseShow()
    {

        $resume = Resume::selOne(['u_id' => session('u_id')]);
        $re_all = ResumeReseale::selWhole(['r_id' => $resume['r_id']]);
//        print_r($re_all);die;
        if ($re_all) {
            foreach ($re_all as $k => $v) {
                //查询出当前投递的简历
                $arr[] = ResumeReseale::selRes(['resume_reseale.rere_id' => $v['rere_id']]);
            }
//            print_r($arr);die;
            foreach ($arr as $ke => $ve) {
                //全部投递的简历
                $reList['all'][] = $ve;
                //投递成功
                if($ve['remuse_resele']==0){
                    $reList['remuse_0'][]=$ve;
                }
                //查看过的简历
                if($ve['remuse_resele']==1){
                    $reList['remuse_1'][]=$ve;
                }
                //简历初试通过
                if($ve['remuse_resele']==2){
                    $reList['remuse_2'][]=$ve;
                }
                //通知面试
                if($ve['remuse_resele']==3||$ve['remuse_resele']==6){
                    $reList['remuse_3'][]=$ve;
                }
                
                //不合格
                if($ve['remuse_resele']==4){
                    $reList['remuse_4'][]=$ve;
                }
            }

        } else {
            $reList[] = '';

        }


        return view('index.resume.delivery', [
            'reList' => $reList,
        ]);
    }


    /**简历预览
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function previewList($id)
    {
        $data['education'] = Education::selAll();
        //个人简历信息查询
        $data['resume'] = Resume::selOne(['r_id' => $id]);

        // 作品查询
        $data['works'] = Works::selAll(['r_id' => $id]);

        // 项目查询
        $data['porject'] = Porject::selAll(['r_id' => $id]);

        //工作查询
        $data['expected'] = Expected::SelOne(['r_id' => $id]);

        //教育背景查询
        $data['school'] = School::selOne(['r_id' => $id]);

//        print_r($data);die;
        return view('index.resume.preview', $data);
    }

}
