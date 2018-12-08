<?php
/**
 * Created by PhpStorm.
 * User: Zedom
 * Date: 2018/12/7
 * Time: 11:45
 */

namespace app\index\controller;

use app\common\model\Chapter;
use app\common\model\Course;
use app\common\model\Section;
use think\Db;
use think\facade\Session;
use think\Request;

class Ccourse
{

    /**
     * Writer:      吴潘安
     * Date:        2018/12/7
     * Function:    返回新建课程页面
     */
    public function newcourse(){
        return view();
    }

    /**
     * Writer:      吴潘安
     * Date:        2018/12/7
     * Function:    返回课程页面
     */
    public function course(){
        return view();
    }

    /**
     * Writer:      吴潘安
     * Date:        2018/12/7
     * Function:    获取所有课程的列表
     */
    public function getCourseList(){
        $list = Course::select();
        $courseList = array();
        $responseStatus = 0;
        foreach ($list as $key=>$value){
            $singleCourse = array();
            array_push($singleCourse, [
                    "name"=>$value["courseName"],
                    "profile"=>$value["shortSummary"],
                    "courseID"=>$value["id"]]);
            array_push($courseList, $singleCourse);
        }
        return json_encode(([
                "responseStatus"=>$responseStatus,
                "courseList"=>$courseList]));
    }

    /**
     * Writer:      吴潘安
     * Date:        2018/12/7
     * Function:    增加课程接口，接收前端数据并向数据库中插入新课程
     */
    public function addCourse(Request $request)
    {
        $post = $request->post();
        $response = "";
        // 向数据库中插入数据
        $effect = Course::insert([
            "preCourseID" => $post["preCourseID"],
            "courseName" => $post["courseName"],
            "shortSummary" => $post["shortSummary"],
            "longSummary" => $post["longSummary"]
        ]);
        if ($effect == 1)
            $response = array("responseStatus" => 0);
        return json_encode($response);
    }

    /**
     * Writer:      吴潘安
     * Date:        2018/12/7
     * Function:    增加章节接口，向数据库中插入新章节
     */
    public function addChapter(Request $request)
    {
        $post = $request->post();
        $courseID = Session::get("courseID");
        $response = "";
        // 向数据库中插入数据
        $effect = Chapter::insert([
            "chapterTitle" => $post["chapterTitle"],
            "courseID" => $courseID
        ]);
        if ($effect == 1)
            $response = array("responseStatus" => 0);
        return json_encode($response);
    }

    /**
     * Writer:      吴潘安
     * Date:        2018/12/7
     * Function:    增加小节接口，向数据库中插入新小节
     */
    public function addSection(Request $request)
    {
        $post = $request->post();
        $response = "";
        // 向数据库中插入数据
        $effect = Section::insert([
            "title" => $post["title"],
            "content" => $post["content"],
            "chapterID" => $post["chapterID"]
        ]);
        if ($effect == 1)
            $response = array("responseStatus" => 0);
        return json_encode($response);
    }

    /**
     * Writer:      吴潘安
     * Date:        2018/12/7
     * Function:    修改小节接口，根据输入更新数据库中相应小节内容
     */
    public function editSection(Request $request)
    {
        $post = $request->post();
        // 向数据库中更新数据
        $section = new Section;
        // save方法第二个参数为更新条件
        $section->save([
            'title' => $post["title"],
            'content' => $post["content"]
        ],['id' => $post["sectionID"]]);

        $response = array("responseStatus" => 0);
        return json_encode($response);
    }

    /**
     * Writer:      吴潘安
     * Date:        2018/12/7
     * Function:    进入课程板块接口，给Session传课程ID
     */
    public function selectCourse(Request $request){
        $post = $request->post();
        Session::set("courseID",$post["courseID"]);
    }

    /**
     * Writer:      吴潘安
     * Date:        2018/12/7
     * Function:    获取课程内容：名字和介绍
     */
    public function getCourse(Request $request){
        $courseID = Session::get("courseID");
        $course = Course::get($courseID);
        $resultArray = [
            "responseStatus"=>0,
            "name"=>$course["courseName"],
            "summary"=>$course["longSummary"]
        ];
        return json_encode($resultArray);
    }

    /**
     * Writer:      吴潘安
     * Date:        2018/12/7
     * Function:    获取章节小节列表，保存小节ID数组并存入session
     */
    public function getChapter(Request $request){
        $courseID = Session::get("courseID");
        $get = $request->get();
        $result = Chapter::where("courseID","=", $courseID)->select();
        $chapter = array();
        $sectionIDList = array();
        foreach($result as $key=>$value){
            $chapterID = $value["id"];
            $sections = Section::where("chapterID","=", $chapterID)->select();
            $sectionList = array();
            foreach ($sections as $k=>$section){
                $singleSection = [
                    "sectionID"=>$section["id"],
                    "sectionName"=>$section["title"],
                ];
                array_push($sectionIDList, $section["id"]);
                array_push($sectionList, $singleSection);
            }
            $singleChapter = [
                "chapterID"=>$value["id"],
                "chapterName"=>$value["chapterTitle"],
                "section"=>$sectionList
            ];
            array_push($chapter, $singleChapter);
        }
        Session::set("sectionIDList", $sectionIDList);
        return json_encode([
            "responseStatus"=>0,
            "chapter"=>$chapter]);
    }

    /**
     * Writer:      吴潘安
     * Date:        2018/12/7
     * Function:    获取小节内容, 计算用户学习进度
     */
    public function getContent(Request $request){
        $get = $request->get();
        $sectionID = $get["sectionID"];
        $section = Section::get($sectionID);
        $resultArray = [
            "responseStatus"=>0,
            "title" => $section["title"],
            "content" => $section["content"]
        ];

        $sectionIDList = Session::get("sectionIDList");
        $count = count($sectionIDList);

        $place = array_search($sectionID, $sectionIDList);
        $percent = ($place+1)/$count;
        $userID = Session::get("userID");
        $courseID = Session::get("courseID");
        $instance = Db::table("usercourse")
            ->where('userID',$userID)
            ->where('courseID',$courseID)
            ->find();

        $response = 0;
        if($instance){
            $instance->save([
                "learningProgress" => max($instance["learningProgress"], $percent)
            ]);
        }else{
            Db::table("usercourse")->insert([
                "userID" => $userID,
                "courseID" => $courseID,
                "learningProgress" => $percent
            ]);
            $response = 1;
        }

        return $response;
            //json_encode($resultArray);
    }

    /**
     * Writer:      吴潘安
     * Date:        2018/12/7
     * Function:    修改课程接口，根据输入更新数据库中课程名、简介
     */
    public function editCourse(Request $request)
    {
        $post = $request->post();
        // 向数据库中更新数据
        $course = new Course;
        // save方法第二个参数为更新条件
        $course->save([
            'courseName' => $post["name"],
            'preCourseID' => $post["preCourseID"],
            'shortSummary' => $post["shortSummary"],
            'longSummary' => $post["longSummary"]
        ],['id' => $post["courseID"]]);

        $response = array("responseStatus" => 0);
        return json_encode($response);
    }
}